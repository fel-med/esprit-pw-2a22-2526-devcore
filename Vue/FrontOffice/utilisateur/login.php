<?php
require_once '../../../Controleur/utilisateurC.php';

$loginMessage = "";
$resetMessage = "";

$userC = new UtilisateurC();

if (isset($_POST['login'])) {
    $loginMessage = $userC->login($_POST['email'], $_POST['password']);
}
if (isset($_POST['reset_email'])) {
    $resetMessage = $userC->sendResetLink($_POST['reset_email']);
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

                        <h2 class="fw-bolder mb-4 text-gradient">log in  Account</h2>

                        <form method="POST">

                            <div class="mb-3">
                                <input type="text" name="email" class="form-control" placeholder="email">
                            </div>

                            <div class="mb-3">
                                <input type="password" name="password" class="form-control" placeholder="password">
                            </div>

                           

                           

                            <button name="login" class="btn btn-primary w-100 py-2">sign in </button>
<video id="video" width="300" autoplay></video>
<button type="button" id="scanLogin">Login avec visage</button>
                        </form>




                        <p class="mt-3 text-muted">
                            you dont  have an account?
                            <a href="register.php">sign up</a>
                        </p>
<p class="mt-2 text-end">
    <a href="#" data-bs-toggle="modal" data-bs-target="#forgotModal">
        Mot de passe oublié ?
    </a>
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
<div class="modal fade" id="forgotModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-4" style="border-radius: 15px;">

      <div class="modal-header border-0">
        <h5 class="modal-title">🔐 Mot de passe oublié</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <form method="POST" id="resetForm">

          <input type="email" name="reset_email" id="emailInput"
                 class="form-control mb-2"
                 placeholder="Entrer votre email" required>

          <small id="emailError" class="text-danger d-none">
            Email invalide
          </small>

          <button name="reset" class="btn btn-primary w-100 mt-2" id="resetBtn">
            <span id="btnText">Envoyer le lien</span>
            <span id="btnLoader" class="spinner-border spinner-border-sm d-none"></span>
          </button>

        </form>

      </div>

    </div>
  </div>
</div>
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
        <script defer src="https://cdn.jsdelivr.net/npm/face-api.js/dist/face-api.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", async () => {

    const video = document.getElementById("video");
    const btn = document.getElementById("scanLogin");

    // caméra
    navigator.mediaDevices.getUserMedia({ video: true })
    .then(stream => video.srcObject = stream);

    // charger modèles
    await faceapi.nets.faceRecognitionNet.loadFromUri('/crea8connect/Esprit-PW-2A22-2526-Devcore/models');
    await faceapi.nets.faceLandmark68Net.loadFromUri('/crea8connect/Esprit-PW-2A22-2526-Devcore/models');
    await faceapi.nets.ssdMobilenetv1.loadFromUri('/crea8connect/Esprit-PW-2A22-2526-Devcore/models');

    btn.onclick = async () => {

        const detection = await faceapi
            .detectSingleFace(video)
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (!detection) {
            alert("Visage non détecté ❌");
            return;
        }

        let descriptor = Array.from(detection.descriptor);

        // envoyer au serveur
        fetch("login_face.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ face: descriptor })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
    window.location.href = data.redirect; // 🔥 dynamique حسب role
} else {
    alert("Utilisateur non reconnu ❌");
}
        });
    };
});
</script>
        <script>
const form = document.getElementById("resetForm");
const emailInput = document.getElementById("emailInput");
const emailError = document.getElementById("emailError");
const btnText = document.getElementById("btnText");
const btnLoader = document.getElementById("btnLoader");

// 📧 validation live
emailInput.addEventListener("input", function() {
    const email = emailInput.value;
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!regex.test(email)) {
        emailError.classList.remove("d-none");
        emailInput.classList.add("is-invalid");
    } else {
        emailError.classList.add("d-none");
        emailInput.classList.remove("is-invalid");
    }
});

// ⏳ loader
form.addEventListener("submit", function() {
    btnText.classList.add("d-none");
    btnLoader.classList.remove("d-none");
});
</script>
<div class="toast position-fixed bottom-0 end-0 m-4" id="toastMsg">
  <div class="toast-body bg-success text-white rounded">
    <?= $resetMessage ?? "" ?>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- 🔥 Script toast -->
<?php if (!empty($resetMessage)) { ?>
<script>
    var toast = new bootstrap.Toast(document.getElementById('toastMsg'));
    toast.show();
</script>
<?php } ?>
    </body>
</html>
