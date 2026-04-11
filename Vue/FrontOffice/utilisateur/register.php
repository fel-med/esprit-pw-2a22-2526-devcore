<?php
require_once '../../../Controleur/utilisateurC.php';

$message = "";

if (isset($_POST['submit'])) {

    if (!empty($_POST['nom']) &&
        filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) &&
        strlen($_POST['password']) >= 6) {

        $user = new Utilisateur(null,$_POST['nom'],$_POST['email'],$_POST['password'],$_POST['role']);
        $userC = new UtilisateurC();
        $message = $userC->ajouterUser($user);
 header("Location: ../utilisateur/login.php");
    } else {
        $message = "Erreur de validation";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Personal - Start Bootstrap Template</title>
        <!-- Favicon-->
        <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
        <!-- Custom Google font-->
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@100;200;300;400;500;600;700;800;900&amp;display=swap" rel="stylesheet" />
        <!-- Bootstrap icons-->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet" />
        <!-- Core theme CSS (includes Bootstrap)-->
        <link href="css/styles.css" rel="stylesheet" />
    </head>
    <body class="d-flex flex-column h-100 bg-light">
        <main class="flex-shrink-0 d-flex align-items-center justify-content-center" style="min-height: 100vh;">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <!-- CARD -->
            <div class="card shadow-lg border-0 overflow-hidden" style="border-radius: 20px;">

                <div class="row g-0">

                    <!-- FORMULAIRE (GAUCHE) -->
                    <div class="col-lg-6 p-5 d-flex flex-column justify-content-center">

                        <h2 class="fw-bolder mb-4 text-gradient">Create Account</h2>

                        <form method="POST">

                            <div class="mb-3">
                                <input type="text" name="nom" class="form-control" placeholder="Nom">
                            </div>

                            <div class="mb-3">
                                <input type="text" name="email" class="form-control" placeholder="Email">
                            </div>

                            <div class="mb-3">
                                <input type="password" name="password" class="form-control" placeholder="Password">
                            </div>

                            <div class="mb-3">
                                <select name="role" class="form-control">
                                    <option value="createur">Créateur</option>
                                    <option value="marque">Marque</option>
                                    <option value="admin">admin</option>
                                </select>
                            </div>

                            <button name="submit" class="btn btn-primary w-100 py-2">Register</button>
                        </form>

                        <p class="mt-3 text-muted">
                            Already have an account?
                            <a href="login.php">Login</a>
                        </p>

                    </div>

                    <!-- IMAGE (DROITE) -->
                    <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center bg-gradient-primary-to-secondary position-relative">

                        <img src="assets/logo.png" class="img-fluid animate-img" style="max-width: 250px;">

                    </div>

                </div>

            </div>

        </div>
    </div>
</div>

</main>
        
        <!-- Footer-->
        <footer class="bg-white py-4 mt-auto">
            <div class="container px-5">
                <div class="row align-items-center justify-content-between flex-column flex-sm-row">
                    <div class="col-auto"><div class="small m-0">Copyright &copy; Your Website 2023</div></div>
                    <div class="col-auto">
                        <a class="small" href="#!">Privacy</a>
                        <span class="mx-1">&middot;</span>
                        <a class="small" href="#!">Terms</a>
                        <span class="mx-1">&middot;</span>
                        <a class="small" href="#!">Contact</a>
                    </div>
                </div>
            </div>
        </footer>
        <!-- Bootstrap core JS-->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Core theme JS-->
        <script src="js/scripts.js"></script>
    </body>
</html>
