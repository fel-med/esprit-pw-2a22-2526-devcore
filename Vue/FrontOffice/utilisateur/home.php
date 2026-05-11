<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: /Vue/FrontOffice/utilisateur/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Home</title>
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/logo.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/logo.png">
<link rel="apple-touch-icon" href="../../public/images/logo.png">
</head>
<body>

<h2>Bienvenue <?php echo $_SESSION['user']['nom']; ?></h2>

<p>Role: <?php echo $_SESSION['role']; ?></p>

<a href="logout.php">Logout</a>

</body>
</html>