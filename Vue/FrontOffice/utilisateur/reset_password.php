<?php
require_once '../../../Controleur/utilisateurC.php';

$message = "";
$token = $_GET['token'] ?? '';

$userC = new UtilisateurC();

if (isset($_POST['update'])) {
    $message = $userC->resetPassword($_POST['password'], $_POST['token']);
    if (stripos($message, 'mis') !== false) {
        $loginUrl = function_exists('cc_app_url')
            ? cc_app_url('Vue/FrontOffice/utilisateur/login.php?reset=success')
            : 'login.php?reset=success';
        header('Location: ' . $loginUrl);
        exit;
    }
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
.auth-shell { min-height: 100vh; }
.auth-topbar { border-bottom: 1px solid rgba(0, 0, 0, 0.06); }
.auth-main { padding: 2rem 0; }
.auth-home-link { border-radius: 999px; font-weight: 700; }
.auth-brand-logo {
    width: 235px;
    height: auto;
    max-height: 72px;
    object-fit: contain;
    display: block;
}
.public-lang-switch { display:inline-flex; gap:.25rem; border:1px solid rgba(78,84,200,.22); border-radius:999px; padding:.2rem; background:#fff; }
.public-lang-switch button { border:0; border-radius:999px; background:transparent; color:#5f6674; font-weight:800; font-size:.72rem; padding:.25rem .55rem; }
.public-lang-switch button.is-active { background:#4e54c8; color:#fff; }
@media (max-width: 575.98px) {
    .auth-brand-logo { width: 175px; max-height: 56px; }
    .auth-main { padding: 1.25rem 0; }
}
</style>
<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
</head>

<body class="d-flex flex-column h-100 bg-light auth-shell">
<header class="auth-topbar bg-white">
    <div class="container-fluid px-3 px-lg-4 d-flex align-items-center justify-content-between gap-3">
        <a class="navbar-brand m-0 d-inline-flex align-items-center" href="index.php"><img src="../../public/images/logoweb.png" alt="Cre8Connect" class="auth-brand-logo"></a>
        <div class="d-flex align-items-center gap-2">
            <div class="public-lang-switch" aria-label="Language">
                <button type="button" data-lang-choice="en">EN</button>
                <button type="button" data-lang-choice="fr">FR</button>
            </div>
            <a class="btn btn-outline-dark btn-sm auth-home-link px-3" href="index.php">&larr; <span data-i18n="auth.home">Home</span></a>
        </div>
    </div>
</header>

<main class="flex-grow-1 d-flex align-items-center justify-content-center auth-main">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-6">

            <div class="card shadow-lg border-0 p-5 text-center" style="border-radius:20px;">

                <h2 class="fw-bold mb-4 text-gradient" data-i18n="auth.resetTitle">New password</h2>

                <form method="POST">

                    <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="mb-3">
    <input type="password" name="password" id="password"
           class="form-control"
           placeholder="New password" data-i18n-placeholder="auth.newPassword" required>
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
                    <div class="alert alert-danger mt-3">
                        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php } ?>

            </div>

        </div>
    </div>
</div>
</main>
<script src="../layout/front-translate.js"></script>
<script>
const cre8ResetTranslations = {
    en: {
        'auth.resetTitle': 'New password',
        'auth.newPassword': 'New password',
        'auth.updatePassword': 'Update password',
        'auth.home': 'Home',
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
        'auth.home': 'Accueil',
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
const password = document.getElementById("password");
const bar = document.getElementById("strengthBar");
const text = document.getElementById("strengthText");

// password strength
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
<?php require __DIR__ . '/../layout/footer.php'; ?>
</body>
</html>

