<?php
require_once '../../../../Controleur/utilisateurC.php';

$message = "";

if (isset($_POST['login'])) {
    $userC = new UtilisateurC();
    $message = $userC->login($_POST['email'], $_POST['password']);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>

<h2>Connexion</h2>
<p><?php echo $message; ?></p>

<form method="POST">
    <input type="text" name="email" placeholder="Email"><br><br>
    <input type="password" name="password" placeholder="Mot de passe"><br><br>
    <button name="login">Se connecter</button>
</form>

<a href="register.php">Register</a>

</body>
</html>