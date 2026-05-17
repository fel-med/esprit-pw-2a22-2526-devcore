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
.public-lang-switch { position:fixed; top:16px; right:16px; display:inline-flex; gap:.25rem; border:1px solid rgba(78,84,200,.22); border-radius:999px; padding:.2rem; background:#fff; z-index:10; }
.public-lang-switch button { border:0; border-radius:999px; background:transparent; color:#5f6674; font-weight:800; font-size:.72rem; padding:.25rem .55rem; }
.public-lang-switch button.is-active { background:#4e54c8; color:#fff; }
</style>
<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
</head>

<body class="d-flex align-items-center justify-content-center bg-light" style="min-height:100vh;">
<div class="public-lang-switch" aria-label="Language">
    <button type="button" data-lang-choice="en">EN</button>
    <button type="button" data-lang-choice="fr">FR</button>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-6">

            <div class="card shadow-lg border-0 p-5 text-center" style="border-radius:20px;">

                <h2 class="fw-bold mb-4 text-gradient" data-i18n="auth.resetTitle">New password</h2>

                <form method="POST">

                    <input type="hidden" name="token" value="<?= $token ?>">

                    <div class="mb-3">
                        <div class="input-group mb-3">
    <input type="password" name="password" id="password"
           class="form-control"
           placeholder="New password" data-i18n-placeholder="auth.newPassword" required>

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
                        <span data-i18n="auth.updatePassword">Update password</span>
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
<script src="../layout/front-translate.js"></script>
<script>
const cre8ResetTranslations = {
    en: {
        'auth.resetTitle': 'New password',
        'auth.newPassword': 'New password',
        'auth.updatePassword': 'Update password',
        'auth.weak': 'Weak',
        'auth.medium': 'Medium',
        'auth.good': 'Good',
        'auth.strong': 'Strong',
        'auth.tooShort': 'Password is too short'
    },
    fr: {
        'auth.resetTitle': 'Nouveau mot de passe',
        'auth.newPassword': 'Nouveau mot de passe',
        'auth.updatePassword': 'Mettre a jour',
        'auth.weak': 'Faible',
        'auth.medium': 'Moyen',
        'auth.good': 'Bon',
        'auth.strong': 'Fort',
        'auth.tooShort': 'Mot de passe trop court'
    }
};
function cre8ResetLang() { if (typeof cre8FrontReadLang === 'function') return cre8FrontReadLang(); try { return (localStorage.getItem('cre8_front_lang') || localStorage.getItem('cre8_lang')) === 'fr' ? 'fr' : 'en'; } catch(e) { return 'en'; } }
function cre8ResetText(key) { const l = cre8ResetLang(); return (cre8ResetTranslations[l] && cre8ResetTranslations[l][key]) || cre8ResetTranslations.en[key] || key; }
function cre8RegisterResetTranslations() { if (typeof cre8RegisterTranslations === 'function') cre8RegisterTranslations(cre8ResetTranslations); document.title = cre8ResetLang() === 'fr' ? 'Reinitialiser le mot de passe' : 'Reset Password'; }
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', cre8RegisterResetTranslations); else cre8RegisterResetTranslations();
window.addEventListener('cre8:languagechange', cre8RegisterResetTranslations);
</script>
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
            text.textContent = cre8ResetText("auth.weak");
            break;

        case 2:
            bar.style.width = "50%";
            bar.className = "progress-bar bg-warning";
            text.textContent = cre8ResetText("auth.medium");
            break;

        case 3:
            bar.style.width = "75%";
            bar.className = "progress-bar bg-info";
            text.textContent = cre8ResetText("auth.good");
            break;

        case 4:
            bar.style.width = "100%";
            bar.className = "progress-bar bg-success";
            text.textContent = cre8ResetText("auth.strong");
            break;
    }
});
document.querySelector("form").addEventListener("submit", function(e) {
    if (password.value.length < 6) {
        e.preventDefault();
        text.textContent = cre8ResetText("auth.tooShort");
        bar.className = "progress-bar bg-danger";
        bar.style.width = "25%";
    }
});
</script>
</body>
</html>

