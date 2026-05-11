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
        <title>Cre8Connect - Login</title>
        <!-- Favicon-->
        <!-- Custom Google font-->
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@100;200;300;400;500;600;700;800;900&amp;display=swap" rel="stylesheet" />
        <!-- Bootstrap icons-->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet" />
        <!-- Core theme CSS (includes Bootstrap)-->
        <link href="css/styles.css" rel="stylesheet" />
        <style>
           .text-gradient {
    background: linear-gradient(45deg, #4e54c8, #8f94fb);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.btn-gradient {
    background: linear-gradient(45deg, #4e54c8, #8f94fb);
    border: none;
    color: white;
    font-weight: 600;
    transition: 0.3s;
}

.btn-gradient:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}

.input-custom {
    border-radius: 10px;
    padding: 12px;
    border: 1px solid #ddd;
    transition: 0.3s;
}

.input-custom:focus {
    border-color: #4e54c8;
    box-shadow: 0 0 8px rgba(78, 84, 200, 0.3);
} </style>
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/logo.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/logo.png">
<link rel="apple-touch-icon" href="../../public/images/logo.png">
    </head>
    <body class="d-flex flex-column h-100 bg-light">
        <main class="flex-shrink-0 d-flex align-items-center justify-content-center" style="min-height: 100vh;">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <!-- CARD -->
            <div class="card shadow-lg border-0 overflow-hidden" style="border-radius: 20px;">

                <div class="row g-0">

            <div class="col-lg-6 p-5 d-flex flex-column justify-content-center">

    <h2 class="fw-bold mb-4 text-gradient text-center">Log in to your account</h2>

    <?php if (!empty($loginMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
            ⚠️ <?php echo htmlspecialchars($loginMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" class="shadow p-4 rounded-4 bg-white">

        <div class="mb-3">
            <input type="text" name="email" class="form-control input-custom" placeholder="Email">
        </div>

        <div class="mb-3">
            <input type="password" name="password" class="form-control input-custom" placeholder="Password">
        </div>

        <button name="login" class="btn btn-gradient w-100 py-2 mb-3">
            Sign in
        </button>

        <!-- 👁 camera -->
        <div class="text-center mb-2">
            <video id="video" width="250" autoplay style="display:none;" class="rounded-3 shadow"></video>
        </div>

        <button type="button" class="btn btn-outline-primary w-100 py-2" id="scanLogin">
            📸 Log in with Face
        </button>

    </form>

    <p class="mt-3 text-center text-muted">
        You don't have an account?
        <a href="register.php" class="fw-bold text-gradient">Sign up</a>
    </p>

    <p class="text-center">
        <a href="#" data-bs-toggle="modal" data-bs-target="#forgotModal" class="text-decoration-none">
            Forgot password?
        </a>
    </p>

</div>

                    <!-- IMAGE (RIGHT) -->
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
        <h5 class="modal-title">🔐 Forgot password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <form method="POST" id="resetForm">

          <input type="email" name="reset_email" id="emailInput"
                 class="form-control mb-2"
                 placeholder="Enter your email" required>

          <small id="emailError" class="text-danger d-none">
            Invalid email
          </small>

          <button name="reset" class="btn btn-primary w-100 mt-2" id="resetBtn">
            <span id="btnText">Send link</span>
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
<script defer src="https://cdn.jsdelivr.net/npm/face-api.js/dist/face-api.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", async () => {

    const video = document.getElementById("video");
    const btn = document.getElementById("scanLogin");

    let stream = null;
    let modelsLoaded = false;

    btn.onclick = async () => {

        // 🎥 activate camera only on click
        if (!stream) {
            stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;
            video.style.display = "block";
        }

        // 📦 load models only once
        if (!modelsLoaded) {
            const currentPath = window.location.pathname.replace(/\/[^\/]*$/, '');
            const modelBase = window.location.origin + currentPath + '/../../../models';
            await faceapi.nets.faceRecognitionNet.loadFromUri(modelBase);
            await faceapi.nets.faceLandmark68Net.loadFromUri(modelBase);
            await faceapi.nets.ssdMobilenetv1.loadFromUri(modelBase);
            modelsLoaded = true;
        }

        const detection = await faceapi
            .detectSingleFace(video)
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (!detection) {
            alert("Face not detected ❌");
            return;
        }

        let descriptor = Array.from(detection.descriptor);

        fetch("login_face.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ face: descriptor })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {

                // 🛑 close camera after login
                stream.getTracks().forEach(track => track.stop());

                window.location.href = data.redirect;
            } else {
                const message = data.message || "User not recognized ❌";
                alert(`${message} \nDistance: ${Number(data.distance).toFixed(4)}`);
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

// 📧 live validation
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

<!-- 🔥 Toast script -->
<?php if (!empty($resetMessage)) { ?>
<script>
    var toast = new bootstrap.Toast(document.getElementById('toastMsg'));
    toast.show();
</script>
<?php } ?>
    </body>
</html>
