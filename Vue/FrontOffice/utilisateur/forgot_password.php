<?php
require_once '../../../Controleur/utilisateurC.php';

$msg = "";

if (isset($_POST['reset'])) {
    $userC = new UtilisateurC();
    $msg = $userC->sendResetLink($_POST['email']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cre8Connect - Forgot Password</title>
    <link href="css/styles.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .public-lang-switch { display:inline-flex; gap:.25rem; border:1px solid rgba(78,84,200,.22); border-radius:999px; padding:.2rem; background:#fff; }
        .public-lang-switch button { border:0; border-radius:999px; background:transparent; color:#5f6674; font-weight:800; font-size:.72rem; padding:.25rem .55rem; }
        .public-lang-switch button.is-active { background:#4e54c8; color:#fff; }
    </style>
    <link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
    <link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
</head>
<body class="bg-light min-vh-100 d-flex align-items-center justify-content-center">
<main class="container" style="max-width:520px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="login.php" class="btn btn-outline-dark btn-sm">&larr; <span data-i18n="auth.backLogin">Back to login</span></a>
        <div class="public-lang-switch" aria-label="Language">
            <button type="button" data-lang-choice="en">EN</button>
            <button type="button" data-lang-choice="fr">FR</button>
        </div>
    </div>
    <section class="card shadow border-0 p-4" style="border-radius:20px;">
        <h1 class="h3 fw-bold mb-2" data-i18n="auth.forgotPassword">Forgot password</h1>
        <p class="text-muted" data-i18n="auth.forgotHelp">Enter your email and we will send you a reset link.</p>
        <form method="POST">
            <input type="email" name="email" placeholder="Email" data-i18n-placeholder="auth.email" class="form-control mb-3" required>
            <button name="reset" class="btn btn-primary w-100" data-i18n="auth.sendLink">Send link</button>
            <?php if ($msg !== ''): ?>
                <p class="mt-3 mb-0"><?php echo htmlspecialchars($msg); ?></p>
            <?php endif; ?>
        </form>
    </section>
</main>
<script src="../layout/front-translate.js"></script>
<script>
(function () {
    var translations = {
        en: {
            'auth.backLogin': 'Back to login',
            'auth.forgotPassword': 'Forgot password',
            'auth.forgotHelp': 'Enter your email and we will send you a reset link.',
            'auth.email': 'Email',
            'auth.sendLink': 'Send link'
        },
        fr: {
            'auth.backLogin': 'Retour a la connexion',
            'auth.forgotPassword': 'Mot de passe oublie',
            'auth.forgotHelp': 'Entrez votre email et nous vous enverrons un lien de reinitialisation.',
            'auth.email': 'Email',
            'auth.sendLink': 'Envoyer le lien'
        }
    };
    function registerTranslations() {
        if (typeof cre8RegisterTranslations === 'function') cre8RegisterTranslations(translations);
        document.title = (typeof cre8FrontReadLang === 'function' && cre8FrontReadLang() === 'fr') ? 'Cre8Connect - Mot de passe oublie' : 'Cre8Connect - Forgot Password';
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', registerTranslations); else registerTranslations();
    window.addEventListener('cre8:languagechange', registerTranslations);
})();
</script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
</body>
</html>
