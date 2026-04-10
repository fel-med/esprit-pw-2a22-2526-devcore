<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../FrontOffice/auth/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
</head>
<body>

<h1>Dashboard Admin</h1>

<a href="utilisateur/list.php">Gérer utilisateurs</a><br>
<a href="../FrontOffice/logout.php">Logout</a>

</body>
</html>