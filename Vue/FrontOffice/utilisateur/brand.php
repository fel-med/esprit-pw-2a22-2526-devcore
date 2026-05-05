<?php
session_start();

if (!isset($_SESSION['user'])) {
    die("Utilisateur non connecté");
}
?>
<html lang="en"><head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="">
        <meta name="author" content="">
        <title>Modern Business - Start Bootstrap Template</title>
        <!-- Favicon-->
        <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
        <!-- Custom Google font-->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@100;200;300;400;500;600;700;800;900&amp;display=swap" rel="stylesheet">
        <!-- Bootstrap icons-->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
        <!-- Core theme CSS (includes Bootstrap)-->
        <link href="css/styles.css" rel="stylesheet">
        <style>
            /* Mode Jour/Nuit - CSS Complet */
            body.light-mode {
                background-color: #1a1a1a !important;
                color: #ffffff !important;
            }

            .light-mode * {
                background-color: #1a1a1a !important;
                color: #ffffff !important;
            }

            .light-mode .navbar {
                background-color: #0d0d0d !important;
                border-bottom: 1px solid #333 !important;
            }

            .light-mode .navbar-light .navbar-nav .nav-link {
                color: #ffffff !important;
            }

            .light-mode .card {
                background-color: #2d2d2d !important;
                border-color: #444 !important;
                color: #ffffff !important;
            }

            .light-mode .card-body {
                background-color: #2d2d2d !important;
                color: #ffffff !important;
            }

            .light-mode .form-control,
            .light-mode .form-select {
                background-color: #3d3d3d !important;
                color: #ffffff !important;
                border-color: #555 !important;
            }

            .light-mode .form-control:focus,
            .light-mode .form-select:focus {
                background-color: #3d3d3d !important;
                color: #ffffff !important;
                border-color: #9B5DE0 !important;
                box-shadow: 0 0 0 0.2rem rgba(155, 93, 224, 0.25) !important;
            }

            .light-mode .modal-content {
                background-color: #2d2d2d !important;
                color: #ffffff !important;
            }

            .light-mode .modal-header {
                background-color: #1a1a1a !important;
                border-bottom-color: #444 !important;
            }

            .light-mode .modal-title {
                color: #ffffff !important;
            }

            .light-mode .alert {
                background-color: #2d2d2d !important;
                color: #ffffff !important;
                border-color: #444 !important;
            }

            .light-mode footer {
                background-color: #0d0d0d !important;
                color: #ffffff !important;
                border-top: 1px solid #333 !important;
            }

            .light-mode .text-muted {
                color: #aaaaaa !important;
            }

            .light-mode .text-gradient {
                color: #D78FEE !important;
            }

            .light-mode .bg-light {
                background-color: #1a1a1a !important;
            }

            .light-mode .bg-white {
                background-color: #0d0d0d !important;
            }

            /* Transitions lisses */
            * {
                transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
            }
        </style>
    </head>
    <body class="d-flex flex-column h-100 bg-light">
        <main class="flex-shrink-0">
            <!-- Navigation-->
            <nav class="navbar navbar-expand-lg navbar-light bg-white py-3">
                <div class="container px-5">
                    <a class="navbar-brand" href="index.html"><span class="fw-bolder text-primary">Cre8connect</span></a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 small fw-bolder">
                            <?php if (isset($_SESSION['nom'])): ?>
    <span class="nav-link">👤 <?php echo $_SESSION['nom']; ?></span>
<?php endif; ?>
                            <li class="nav-item"><a class="nav-link" href="brand.php">Home</a></li>
                            <li class="nav-item"><a class="nav-link" href="reclamation.php">Reclamation</a></li>
                            <li class="nav-item">
                                <a class="nav-link" href="#" onclick="toggleDarkMode(); return false;" title="Mode jour/nuit">
                                    <i class="bi bi-moon-stars" id="themeIcon"></i>
                                </a>
                            </li>
                           <li class="nav-item">
    <a class="nav-link text-danger" href="logout.php">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>
</li>
                        </ul>
                    </div>
                </div>
            </nav>
            <!-- Projects Section-->
            <section class="py-5">
                <div class="container px-5 mb-5">
                    <div class="text-center mb-5">
                        <h1 class="display-5 fw-bolder mb-0"><span class="text-gradient d-inline">brand</span></h1>
                        <div class="text-center mt-3">
    <p class="text-muted">
        hello  : <strong><?php echo $_SESSION['nom'] ?? 'Utilisateur'; ?></strong>
    </p>
</div>
                    </div>
                    
                </div>
            </section>
            <!-- Call to action section-->
            <section class="py-5 bg-gradient-primary-to-secondary text-white">
                <div class="container px-5 my-5">
                    <div class="text-center">
                        <h2 class="display-4 fw-bolder mb-4">Let's build something together</h2>
                        
                    </div>
                </div>
            </section>
        </main>
        <!-- Footer-->
        <footer class="bg-white py-4 mt-auto">
            <div class="container px-5">
                <div class="row align-items-center justify-content-between flex-column flex-sm-row">
                    <div class="col-auto"><div class="small m-0">Copyright © Your Website 2023</div></div>
                    <div class="col-auto">
                        <a class="small" href="#!">Privacy</a>
                        <span class="mx-1">·</span>
                        <a class="small" href="#!">Terms</a>
                        <span class="mx-1">·</span>
                        <a class="small" href="#!">Contact</a>
                    </div>
                </div>
            </div>
        </footer>
        <!-- Bootstrap core JS-->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Core theme JS-->
        <script src="js/scripts.js"></script>

        <!-- Mode Jour/Nuit JavaScript -->
        <script>
            function toggleDarkMode() {
                document.body.classList.toggle('light-mode');
                
                let icon = document.getElementById('themeIcon');
                if (document.body.classList.contains('light-mode')) {
                    localStorage.setItem('theme', 'light');
                    if (icon) icon.className = 'bi bi-brightness-high';
                } else {
                    localStorage.setItem('theme', 'dark');
                    if (icon) icon.className = 'bi bi-moon-stars';
                }
            }
            
            // Appliquer le thème au chargement
            window.addEventListener('DOMContentLoaded', function() {
                let icon = document.getElementById('themeIcon');
                if (localStorage.getItem('theme') === 'light') {
                    document.body.classList.add('light-mode');
                    if (icon) icon.className = 'bi bi-brightness-high';
                }
            });
        </script>
    

</body></html>