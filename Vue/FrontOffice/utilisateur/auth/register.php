<?php
require_once '../../../../Controleur/utilisateurC.php';

$message = "";

if (isset($_POST['submit'])) {

    if (!empty($_POST['nom']) &&
        filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) &&
        strlen($_POST['password']) >= 6) {

        $user = new Utilisateur(null,$_POST['nom'],$_POST['email'],$_POST['password'],$_POST['role']);
        $userC = new UtilisateurC();
        $message = $userC->ajouterUser($user);

    } else {
        $message = "Erreur de validation";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>

<h2>Inscription</h2>
<p><?php echo $message; ?></p>

<form method="POST">
    <input type="text" name="nom" placeholder="Nom"><br><br>
    <input type="text" name="email" placeholder="Email"><br><br>
    <input type="password" name="password" placeholder="Mot de passe"><br><br>

    <select name="role">
        <option value="createur">Créateur</option>
        <option value="marque">Marque</option>
        <option value="admin">admin</option>
    </select><br><br>

    <button name="submit">S'inscrire</button>
</form>

<a href="login.php">Login</a>

</body>
</html>