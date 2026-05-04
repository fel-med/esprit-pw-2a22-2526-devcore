<?php
require_once '../../../Controleur/utilisateurC.php';

$message = "";
$faceDescriptor = $_POST['faceDescriptor'];

if (empty($faceDescriptor)) {
    die("Veuillez enregistrer votre visage ❌");
}

// stocker dans base de données
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ✅ vérifier si captcha existe
    if (empty($_POST['g-recaptcha-response'])) {
        die("Veuillez valider le reCAPTCHA ❌");
    }

    $secret = "6Le_S9ksAAAAAOEjx9cRk48RuR3fYR1RxZrSWtYk";
    $response = $_POST['g-recaptcha-response'];

    $verify = file_get_contents(
        "https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$response"
    );

    $result = json_decode($verify);

    if (!$result->success) {
        die("Vérification humaine échouée ❌");
    }

    // ✅ TON CODE NORMAL
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
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <script defer src="https://cdn.jsdelivr.net/npm/face-api.js"></script>
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

                        <form id="registerForm" method="POST" >

    <div class="mb-3">
        <input type="text" id="nom" name="nom" class="form-control" placeholder="Nom">
        <small id="nomError" class="text-danger"></small>
    </div>

    <div class="mb-3">
        <input type="text" id="email" name="email" class="form-control" placeholder="Email">
        <small id="emailError" class="text-danger"></small>
    </div>

    <div class="mb-3">
        <input type="password" id="password" name="password" class="form-control" placeholder="Password">
        <small id="passwordError" class="text-danger"></small>
    </div>

    <div class="mb-3">
        <select id="role" name="role" class="form-control">
            <option value="">Choisir un rôle</option>
            <option value="createur">Créateur</option>
            <option value="marque">Marque</option>
            <option value="admin">Admin</option>
        </select>
        <small id="roleError" class="text-danger"></small>
    </div>
   <div class="g-recaptcha" data-sitekey="6Le_S9ksAAAAALQ8QeII5XANm_kyXmRF-Sq5OBt8"></div>

      <br/>
      <video id="video" width="300" autoplay></video>
<button type="button" onclick="registerFace()">Enregistrer visage</button>

<input type="hidden" name="faceDescriptor" id="faceDescriptor">
    <button type="submit" class="btn btn-primary w-100">Register</button>

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
        <script src="js/scripts.js">
            
        </script>
        <script>

// récupération des champs
let nom = document.getElementById("nom");
let email = document.getElementById("email");
let password = document.getElementById("password");
let role = document.getElementById("role");

// ===== NOM =====
nom.addEventListener("input", function () {
    let error = document.getElementById("nomError");

    if (nom.value.trim() === "") {
        error.textContent = "Le nom est requis";
        nom.classList.add("is-invalid");
    } 
    else if (nom.value.length < 3) {
        error.textContent = "Le nom doit contenir au moins 3 caractères";
        nom.classList.add("is-invalid");
    }
    else if (!/^[a-zA-Z\s]+$/.test(nom.value)) {
        error.textContent = "Le nom ne doit contenir que des lettres";
        nom.classList.add("is-invalid");
    }
    else {
        error.textContent = "";
        nom.classList.remove("is-invalid");
        nom.classList.add("is-valid");
    }
});

// ===== EMAIL =====
email.addEventListener("input", function () {
    let error = document.getElementById("emailError");
    let regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (email.value.trim() === "") {
        error.textContent = "L'email est requis";
        email.classList.add("is-invalid");
    }
    else if (!regex.test(email.value)) {
        error.textContent = "Veuillez entrer une adresse email valide";
        email.classList.add("is-invalid");
    }
    else {
        error.textContent = "";
        email.classList.remove("is-invalid");
        email.classList.add("is-valid");
    }
});

// ===== PASSWORD =====
password.addEventListener("input", function () {
    let error = document.getElementById("passwordError");

    if (password.value.trim() === "") {
        error.textContent = "Le mot de passe est requis";
        password.classList.add("is-invalid");
    }
    else if (password.value.length < 6) {
        error.textContent = "Minimum 6 caractères";
        password.classList.add("is-invalid");
    }
    else {
        error.textContent = "";
        password.classList.remove("is-invalid");
        password.classList.add("is-valid");
    }
});

// ===== ROLE =====
role.addEventListener("change", function () {
    let error = document.getElementById("roleError");

    if (role.value === "") {
        error.textContent = "Veuillez sélectionner un rôle";
        role.classList.add("is-invalid");
    } else {
        error.textContent = "";
        role.classList.remove("is-invalid");
        role.classList.add("is-valid");
    }
});

// ===== SUBMIT =====
document.getElementById("registerForm").addEventListener("submit", function (e) {

    if (
        nom.value.trim().length < 3 ||
        !/^[a-zA-Z\s]+$/.test(nom.value) ||
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value) ||
        password.value.length < 6 ||
        role.value === ""
    ) {
        e.preventDefault();
        alert("Veuillez corriger les erreurs avant de continuer !");
    }

});

</script>
<script>
async function registerFace() {

    const video = document.getElementById("video");

    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
    video.srcObject = stream;

    // charger modèles
    await faceapi.nets.faceRecognitionNet.loadFromUri('/models');
    await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
    await faceapi.nets.ssdMobilenetv1.loadFromUri('/models');

    setTimeout(async () => {

        const detection = await faceapi
            .detectSingleFace(video)
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (!detection) {
            alert("Aucun visage ❌");
            return;
        }

        // convertir en string
        let descriptor = Array.from(detection.descriptor);
        document.getElementById("faceDescriptor").value = JSON.stringify(descriptor);

        alert("Visage enregistré ✅");

    }, 3000);
}
</script>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script src="https://cdn.faceio.net/fio.js"></script>
    </body>
</html>
