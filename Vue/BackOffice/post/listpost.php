<?php
require_once '../../../Controleur/postC.php';

$postC = new PostC();
$list = $postC->listPosts();
?>

<!DOCTYPE html>
<html>
<head>
    <title>List Posts</title>
</head>
<body>

<h2>Liste des Posts</h2>

<a href="addpost.php">➕ Ajouter un Post</a>

<br><br>

<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>Createur</th>
        <th>Subject</th>
        <th>Date</th>
        <th>Actions</th>
    </tr>

    <?php foreach ($list as $post) { ?>
        <tr>
            <td><?= htmlspecialchars($post['id']); ?></td>
            <td><?= htmlspecialchars($post['idCreateur']); ?></td>
            <td><?= htmlspecialchars($post['subject']); ?></td>
            <td><?= htmlspecialchars($post['creationDate']); ?></td>
            <td>
                <a href="deletepost.php?id=<?= urlencode($post['id']); ?>">Delete</a>
                |
                <a href="updatepost.php?id=<?= urlencode($post['id']); ?>">Update</a>
            </td>
        </tr>
    <?php } ?>
</table>

</body>

</html>