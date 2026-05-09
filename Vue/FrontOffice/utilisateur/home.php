<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: auth/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Home</title>
</head>
<body>

<h2>Bienvenue <?php echo $_SESSION['user']['nom']; ?></h2>

<p>Role: <?php echo $_SESSION['role']; ?></p>

<a href="logout.php">Logout</a>

</body>
</html>