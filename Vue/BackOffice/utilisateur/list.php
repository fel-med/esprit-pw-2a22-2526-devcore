<?php
session_start();
require_once '../../../Controleur/utilisateurC.php';
require_once '../../../Controleur/reclamationC.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../FrontOffice/auth/login.php");
    exit;
}

$userC = new UtilisateurC();
$reclamationC = new ReclamationC();

// Pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 4; // Records per page

$usersResult = $userC->afficherUsers('', '', $page, $limit);
$users = is_array($usersResult) && isset($usersResult['data']) ? $usersResult['data'] : [];
$totalUsers = is_array($usersResult) && isset($usersResult['total']) ? intval($usersResult['total']) : 0;
$totalPages = is_array($usersResult) && isset($usersResult['totalPages']) ? max(1, intval($usersResult['totalPages'])) : 1;
$currentPage = is_array($usersResult) && isset($usersResult['page']) ? max(1, intval($usersResult['page'])) : 1;

$stats = $reclamationC->statistiques();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Gestion Utilisateurs - cre8connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .navbar-custom {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 30px;
            margin-bottom: 30px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-custom a, .navbar-custom button {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin: 0 10px;
            border: none;
            background: none;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .navbar-custom a:hover, .navbar-custom button:hover {
            color: #764ba2;
        }

        .header {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .header h2 {
            color: #667eea;
            margin-bottom: 5px;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .table tbody tr {
            transition: background-color 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: #f5f5f5;
        }

        .badge-admin {
            background: linear-gradient(135deg, #9B5DE0 0%, #B771E5 100%);
            color: white;
        }

        .badge-client {
            background: linear-gradient(135deg, #E11D74 0%, #D01565 100%);
            color: white;
        }

        .btn-custom {
            padding: 5px 15px;
            font-size: 0.875rem;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: #667eea;
            color: white;
        }

        .btn-edit:hover {
            background: #5568d3;
            color: white;
        }

        .btn-delete {
            background: #FB5607;
            color: white;
        }

        .btn-delete:hover {
            background: #E74C00;
            color: white;
        }

        .btn-add {
            background: linear-gradient(135deg, #AEEA94 0%, #99D98E 100%);
            color: #2d5016;
            font-weight: bold;
        }

        .btn-add:hover {
            background: linear-gradient(135deg, #99D98E 0%, #84D084 100%);
            color: #1a3d0a;
        }

        .stats-row {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .pagination {
            margin-bottom: 0;
        }

        .pagination .page-link {
            color: #667eea;
            background-color: #fff;
            border: 1px solid #dee2e6;
            padding: 0.375rem 0.75rem;
            margin: 0 2px;
            border-radius: 0.375rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .pagination .page-link:hover {
            color: #5568d3;
            background-color: #f8f9fa;
            border-color: #adb5bd;
        }

        .pagination .page-item.active .page-link {
            background-color: #667eea;
            border-color: #667eea;
            color: white;
        }

        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            background-color: #fff;
            border-color: #dee2e6;
        }

        .stat-card {
            flex: 1;
            min-width: 200px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card h6 {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            color: #666;
        }

        .stat-card h3 {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }

        .light-mode {
            background: #f5f5f5 !important;
            color: #000 !important;
        }

        .light-mode .table-container,
        .light-mode .header,
        .light-mode .stat-card {
            background-color: #f8f9fa !important;
        }

        .light-mode .table tbody tr:hover {
            background-color: #e9ecef !important;
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <div class="navbar-custom">
        <h4 class="mb-0">👥 Gestion Utilisateurs</h4>
        <div>
            <a href="index.php">📊 Dashboard</a>
            <a href="reclamations.php">📋 Réclamations</a>
            <a href="#" onclick="toggleDarkMode(); return false;">🌙 Mode Sombre</a>
            <a href="logout.php">🚪 Déconnexion</a>
        </div>
    </div>

    <!-- Header -->
    <div class="header">
        <h2>👋 Bienvenue sur la gestion des utilisateurs</h2>
        <p class="mb-0 text-muted">Gérez tous les comptes utilisateurs de votre plateforme</p>
    </div>

    <!-- Statistiques -->
    <div class="stats-row">
        <div class="stat-card" style="border-left: 4px solid #667eea;">
            <h6>Total Utilisateurs</h6>
            <h3><?= $totalUsers ?></h3>
        </div>
        <div class="stat-card" style="border-left: 4px solid #9B5DE0;">
            <h6>Administrateurs</h6>
            <h3><?= count(array_filter($users, fn($u) => $u['role'] == 'admin')) ?></h3>
        </div>
        <div class="stat-card" style="border-left: 4px solid #E11D74;">
            <h6>Clients</h6>
            <h3><?= count(array_filter($users, fn($u) => $u['role'] == 'client')) ?></h3>
        </div>
        <div class="stat-card" style="border-left: 4px solid #AEEA94;">
            <h6>Réclamations Traitées</h6>
            <h3><?= $stats['traitee'] ?></h3>
        </div>
    </div>

    <!-- Table -->
    <div class="table-container">
        <div style="padding: 20px; border-bottom: 1px solid #e9ecef;">
            <button class="btn btn-add btn-custom" onclick="addUser()">➕ Ajouter Utilisateur</button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($u['id']) ?></strong></td>
                                <td><?= htmlspecialchars($u['nom']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <span class="badge <?= ($u['role'] == 'admin') ? 'badge-admin' : 'badge-client' ?>">
                                        <?= ucfirst($u['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= ($u['statut'] == 'actif') ? 'success' : 'danger' ?>">
                                        <?= ucfirst($u['statut'] ?? 'Inconnu') ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-custom btn-edit" onclick="editUser(<?= $u['id'] ?>)">✏️ Éditer</button>
                                    <button class="btn-custom btn-delete" onclick="deleteUser(<?= $u['id'] ?>)">🗑️ Supprimer</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <p class="text-muted">Aucun utilisateur trouvé</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-center mt-4">
            <nav aria-label="Pagination utilisateurs">
                <ul class="pagination">
                    <!-- Previous button -->
                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $currentPage - 1 ?>" aria-label="Précédent">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>

                    <!-- Page numbers -->
                    <?php
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);

                    if ($startPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1">1</a>
                        </li>
                        <?php if ($startPage > 2): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $totalPages ?>"><?= $totalPages ?></a>
                        </li>
                    <?php endif; ?>

                    <!-- Next button -->
                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $currentPage + 1 ?>" aria-label="Suivant">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <div class="text-center mt-2 text-muted">
            Page <?= $currentPage ?> sur <?= $totalPages ?> (<?= $totalUsers ?> utilisateurs au total)
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addUser() {
            alert('Fonctionnalité d\'ajout utilisateur à développer');
        }

        function editUser(id) {
            alert('Fonctionnalité d\'édition utilisateur à développer pour l\'utilisateur ID: ' + id);
        }

        function deleteUser(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'delete.php';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'id';
                input.value = id;

                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Mode sombre
        function toggleDarkMode() {
            document.body.classList.toggle("light-mode");
            localStorage.setItem("theme", document.body.classList.contains("light-mode") ? "light" : "dark");
        }

        // Appliquer le thème au chargement
        window.addEventListener('DOMContentLoaded', function() {
            if (localStorage.getItem("theme") === "light") {
                document.body.classList.add("light-mode");
            }
        });
    </script>

</body>
</html>