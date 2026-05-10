<?php
require_once '../../../Controleur/utilisateurC.php';

$msg = "";

if (isset($_POST['reset'])) {
    $userC = new UtilisateurC();
    $msg = $userC->sendResetLink($_POST['email']);
}
?>

<form method="POST">
    <input type="email" name="email" placeholder="Email" class="form-control mb-3" required>
    <button name="reset" class="btn btn-primary w-100">Envoyer</button>
    <p><?= $msg ?></p>
</form>