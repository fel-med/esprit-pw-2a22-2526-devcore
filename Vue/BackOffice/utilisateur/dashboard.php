<?php
session_start();
require_once '../../../Controleur/utilisateurC.php';
require_once '../../../Controleur/reclamationC.php';
require_once '../../../Controleur/session_helper.php';

if (!isset($_SESSION['user']) || !isBackOfficeRole(cc_current_user_role())) {
    header("Location: ../../FrontOffice/utilisateur/login.php");
    exit;
}

$userC = new UtilisateurC();
$reclamationC = new ReclamationC();

$users = $userC->afficherUsers();
$stats = $reclamationC->statistiques();
$reclamations = $reclamationC->afficherReclamationsAdmin();

// Calculate user statistics
$totalUsers = count($users);
$admins = count(array_filter($users, fn($u) => isBackOfficeRole($u['role'] ?? '')));
$clients = count(array_filter($users, fn($u) => $u['role'] == 'client'));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Dashboard Admin - cre8connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .kpi-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 20px;
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }

        .kpi-card .card-body {
            padding: 25px;
            text-align: center;
        }

        .kpi-card h6 {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .kpi-card h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }

        .kpi-card i {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }

        .light-mode {
            background: #f9fafb !important;
            color: #111827 !important;
        }

        .light-mode .kpi-card,
        .light-mode .card,
        .light-mode .card-body,
        .light-mode .navbar-custom,
        .light-mode .table,
        .light-mode .table-responsive,
        .light-mode .dropdown-menu,
        .light-mode .form-control,
        .light-mode .form-select,
        .light-mode .modal-content {
            background-color: #ffffff !important;
            color: #111827 !important;
            border-color: #e2e8f0 !important;
        }

        .light-mode .navbar-custom a,
        .light-mode .nav-link,
        .light-mode .page-title,
        .light-mode .card-title,
        .light-mode .card-description {
            color: #111827 !important;
        }

        .light-mode .table thead {
            background-color: #e2e8f0 !important;
            color: #111827 !important;
        }

        .light-mode .table tbody tr:hover {
            background-color: #f8fafc !important;
        }

        .navbar-custom {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 30px;
            margin-bottom: 30px;
            border-radius: 10px;
        }

        .navbar-custom a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin: 0 15px;
            transition: color 0.3s ease;
        }

        .navbar-custom a:hover {
            color: #764ba2;
        }
    </style>
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/logo.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/logo.png">
<link rel="apple-touch-icon" href="../../public/images/logo.png">
</head>
<body>

    <!-- Navigation -->
    <div class="navbar-custom d-flex justify-content-between align-items-center">
        <h4 class="mb-0">🎛️ Admin Dashboard</h4>
        <div>
            <a href="list.php">👥 Users</a>
            <a href="reclamations.php">📋 Complaints</a>
            <a href="#" onclick="toggleDarkMode(); return false;">🌙 Dark Mode</a>
            <a href="logout.php">🚪 Logout</a>
    <!-- Header -->
    <div class="dashboard-header">
        <h1 class="mb-2">👋 Welcome, <?= htmlspecialchars($_SESSION['user']['nom'] ?? 'Administrator') ?></h1>
        <p class="mb-0">Overview of your cre8connect platform</p>
    </div>

    <!-- KPI Cards - Users -->
    <h4 class="text-white mb-4">📊 User Statistics</h4>
    <div class="row mb-4">

        <div class="col-md-4">
            <div class="card kpi-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body">
                    <i class="mdi mdi-account-multiple"></i>
                    <h6>Total Users</h6>
                    <h3><?= $totalUsers ?></h3>
                    <small class="opacity-75">All active accounts</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card kpi-card" style="background: linear-gradient(135deg, #9B5DE0 0%, #B771E5 100%); color: white;">
                <div class="card-body">
                    <i class="mdi mdi-shield-admin"></i>
                    <h6>Administrators</h6>
                    <h3><?= $admins ?></h3>
                    <small class="opacity-75">Admin accounts</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card kpi-card" style="background: linear-gradient(135deg, #E11D74 0%, #D01565 100%); color: white;">
                <div class="card-body">
                    <i class="mdi mdi-account"></i>
                    <h6>Users</h6>
                    <h3><?= $clients ?></h3>
                    <small class="opacity-75">User accounts</small>
                </div>
            </div>
        </div>

    </div>

    <!-- KPI Cards - Complaints -->
    <h4 class="text-white mb-4">📞 Complaint Statistics</h4>
    <div class="row mb-4">

        <div class="col-md-3">
            <div class="card kpi-card" style="background: linear-gradient(135deg, #9B5DE0 0%, #B771E5 100%); color: white;">
                <div class="card-body">
                    <i class="mdi mdi-file-document"></i>
                    <h6>Total</h6>
                    <h3><?= $stats['total'] ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card kpi-card" style="background: linear-gradient(135deg, #D78FEE 0%, #C96FE8 100%); color: white;">
                <div class="card-body">
                    <i class="mdi mdi-clock"></i>
                    <h6>Pending</h6>
                    <h3><?= $stats['en_attente'] ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card kpi-card" style="background: linear-gradient(135deg, #AEEA94 0%, #99D98E 100%); color: #2d5016;">
                <div class="card-body">
                    <i class="mdi mdi-check-circle"></i>
                    <h6>Processed</h6>
                    <h3><?= $stats['traitee'] ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card kpi-card" style="background: linear-gradient(135deg, #E11D74 0%, #D01565 100%); color: white;">
                <div class="card-body">
                    <i class="mdi mdi-alert"></i>
                    <h6>Urgentes</h6>
                    <h3><?= $stats['haute'] ?></h3>
                </div>
            </div>
        </div>

    </div>

    <!-- Graphes -->
    <div class="row mb-4">

        <!-- Pie Chart - User Distribution -->
        <div class="col-lg-6 mb-4">
            <div class="card" style="border: none; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
                <div class="card-body">
                    <h5 class="card-title mb-4">📊 User Distribution</h5>
                    <div class="chart-container">
                        <canvas id="chartUsers"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie Chart - Complaints -->
        <div class="col-lg-6 mb-4">
            <div class="card" style="border: none; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
                <div class="card-body">
                    <h5 class="card-title mb-4">📋 Complaints Distribution</h5>
                    <div class="chart-container">
                        <canvas id="chartReclamations"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <div style="text-align: center; color: white; padding: 20px;">
        <p>&copy; 2026 cre8connect - All rights reserved</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Consistent color palette
        const colors = {
            total: '#9B5DE0',
            enAttente: '#D78FEE',
            traitee: '#AEEA94',
            haute: '#E11D74',
            admin: '#9B5DE0',
            client: '#E11D74'
        };

        // ===== CHART 1: PIE CHART - Users =====
        const ctxUsers = document.getElementById('chartUsers');
        new Chart(ctxUsers, {
            type: 'pie',
            data: {
                labels: ['Administrators', 'Users'],
                datasets: [{
                    data: [<?= $admins ?>, <?= $clients ?>],
                    backgroundColor: [colors.admin, colors.client],
                    borderColor: ['#fff', '#fff'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: { size: 12, weight: 'bold' }
                        }
                    }
                }
            }
        });

        // ===== CHART 2: DOUGHNUT CHART - Complaints =====
        const ctxReclamations = document.getElementById('chartReclamations');
        new Chart(ctxReclamations, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Processed'],
                datasets: [{
                    data: [<?= $stats['en_attente'] ?>, <?= $stats['traitee'] ?>],
                    backgroundColor: [colors.enAttente, colors.traitee],
                    borderColor: ['#fff', '#fff'],
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: { size: 12, weight: 'bold' }
                        }
                    }
                }
            }
        });

        // Dark mode
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
