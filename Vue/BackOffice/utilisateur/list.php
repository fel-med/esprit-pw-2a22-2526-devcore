<?php
session_start();
require_once '../../../Controleur/utilisateurC.php';

$userC = new UtilisateurC();
$users = $userC->afficherUsers();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Users</title>
</head>
<body>

<h2>Liste utilisateurs</h2>

<table border="1">
<tr>
    <th>ID</th><th>Nom</th><th>Email</th><th>Role</th><th>Action</th>
</tr>

<?php foreach ($users as $u) { ?>
<tr>
    <td><?= $u['id'] ?></td>
    <td><?= $u['nom'] ?></td>
    <td><?= $u['email'] ?></td>
    <td><?= $u['role'] ?></td>
    <td><a href="delete.php?id=<?= $u['id'] ?>">Delete</a></td>
</tr>
<?php } ?>

</table>

</body>
</html>