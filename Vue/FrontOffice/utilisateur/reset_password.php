<?php
require_once '../../../Controleur/utilisateurC.php';

$message = "";
$token = $_GET['token'] ?? '';

$userC = new UtilisateurC();

if (isset($_POST['update'])) {
    $message = $userC->resetPassword($_POST['password'], $_POST['token']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link href="css/styles.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
<title>Reset Password</title>
<style>
.progress-bar {
    transition: width 0.4s ease;
}
</style>
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/logo.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/logo.png">
<link rel="apple-touch-icon" href="../../public/images/logo.png">
</head>

<body class="d-flex align-items-center justify-content-center bg-light" style="min-height:100vh;">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-6">

            <div class="card shadow-lg border-0 p-5 text-center" style="border-radius:20px;">

                <h2 class="fw-bold mb-4 text-gradient">🔐 Nouveau mot de passe</h2>

                <form method="POST">

                    <input type="hidden" name="token" value="<?= $token ?>">

                    <div class="mb-3">
                        <div class="input-group mb-3">
    <input type="password" name="password" id="password"
           class="form-control"
           placeholder="Nouveau mot de passe" required>

    <button type="button" class="btn btn-outline-secondary" id="togglePassword">
        👁️
    </button>
</div>
<!-- Barre -->
    <div class="progress mt-2" style="height: 6px;">
        <div id="strengthBar" class="progress-bar" style="width: 0%"></div>
    </div>

    <!-- Texte -->
    <small id="strengthText" class="text-muted"></small>
</div>
                    </div>

                    <button name="update" class="btn btn-primary w-100 py-2">
                        Mettre à jour
                    </button>

                </form>

                <?php if (!empty($message)) { ?>
                    <div class="alert alert-success mt-3">
                        <?= $message ?>
                    </div>
                <?php } ?>

            </div>

        </div>
    </div>
</div>
<script>
const passwordInput = document.getElementById("password");
const toggleBtn = document.getElementById("togglePassword");

toggleBtn.addEventListener("click", function () {
    const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
    passwordInput.setAttribute("type", type);

    // changer icône
    this.textContent = type === "password" ? "👁️" : "🙈";
});
</script>
<script>
const password = document.getElementById("password");
const bar = document.getElementById("strengthBar");
const text = document.getElementById("strengthText");
const toggleBtn = document.getElementById("togglePassword");

// 👁️ show / hide
toggleBtn.addEventListener("click", function () {
    const type = password.type === "password" ? "text" : "password";
    password.type = type;

    this.innerHTML = type === "password"
        ? '<i class="bi bi-eye"></i>'
        : '<i class="bi bi-eye-slash"></i>';
});

// 🔐 force du mot de passe
password.addEventListener("input", function () {
    const val = password.value;
    let score = 0;

    if (val.length >= 6) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    switch(score) {
        case 0:
        case 1:
            bar.style.width = "25%";
            bar.className = "progress-bar bg-danger";
            text.textContent = "Faible";
            break;

        case 2:
            bar.style.width = "50%";
            bar.className = "progress-bar bg-warning";
            text.textContent = "Moyen";
            break;

        case 3:
            bar.style.width = "75%";
            bar.className = "progress-bar bg-info";
            text.textContent = "Bon";
            break;

        case 4:
            bar.style.width = "100%";
            bar.className = "progress-bar bg-success";
            text.textContent = "Fort 🔥";
            break;
    }
});
document.querySelector("form").addEventListener("submit", function(e) {
    if (password.value.length < 6) {
        e.preventDefault();
        text.textContent = "Mot de passe trop court ❌";
        bar.className = "progress-bar bg-danger";
        bar.style.width = "25%";
    }
});
</script>
</body>
</html>