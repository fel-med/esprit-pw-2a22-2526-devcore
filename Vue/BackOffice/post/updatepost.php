<?php
require_once '../../../Controleur/postC.php';
require_once '../../../Modele/post.php';

$postC = new PostC();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID du post manquant.");
}

$post = $postC->showPost($_GET['id']);

if (!$post) {
    die("Post introuvable.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $updatedPost = new Post(
        $_GET['id'],
        $_POST['idCreateur'],
        $_POST['subject'],
        $_POST['creationDate'],
        $_POST['textContent'],
        $_POST['imageContent'],
        $_POST['VideoContent'],
        $_POST['numberOfView'],
        $_POST['numberOfLike'],
        $_POST['numberOfDislike']
    );

    $postC->updatePost($updatedPost);

    header("Location: listpost.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Post</title>
</head>
<body>

<h2>Modifier Post</h2>

<form method="POST">
    <input type="text" name="idCreateur" value="<?= htmlspecialchars($post['idCreateur']); ?>" required><br><br>
    <input type="text" name="subject" value="<?= htmlspecialchars($post['subject']); ?>" required><br><br>
    <input type="text" name="creationDate" value="<?= htmlspecialchars($post['creationDate']); ?>" required><br><br>
    <textarea name="textContent"><?= htmlspecialchars($post['textContent']); ?></textarea><br><br>
    <input type="text" name="imageContent" value="<?= htmlspecialchars($post['imageContent']); ?>"><br><br>
    <input type="text" name="VideoContent" value="<?= htmlspecialchars($post['VideoContent']); ?>"><br><br>
    <input type="number" name="numberOfView" value="<?= htmlspecialchars($post['numberOfView']); ?>"><br><br>
    <input type="number" name="numberOfLike" value="<?= htmlspecialchars($post['numberOfLike']); ?>"><br><br>
    <input type="number" name="numberOfDislike" value="<?= htmlspecialchars($post['numberOfDislike']); ?>"><br><br>
    <button type="submit">Update</button>
</form>

</body>
</html>