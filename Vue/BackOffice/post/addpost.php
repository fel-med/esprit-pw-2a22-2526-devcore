<?php
require_once '../../../Controleur/postC.php';
require_once '../../../Modele/post.php';

$postC = new PostC();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $idCreateur = $_POST['idCreateur'];
    $subject = $_POST['subject'];
    $creationDate = date('Y-m-d H:i:s');
    $textContent = $_POST['textContent'];

    $imageContent = '';
    $VideoContent = '';

    $numberOfView = 0;
    $numberOfLike = 0;
    $numberOfDislike = 0;

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $imageContent = 'uploads/' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], '../../public/' . $imageContent);
    }

    if (isset($_FILES['video']) && $_FILES['video']['error'] == 0) {
        $VideoContent = 'uploads/' . basename($_FILES['video']['name']);
        move_uploaded_file($_FILES['video']['tmp_name'], '../../public/' . $VideoContent);
    }

    $post = new Post(
        null,
        $idCreateur,
        $subject,
        $creationDate,
        $textContent,
        $imageContent,
        $VideoContent,
        $numberOfView,
        $numberOfLike,
        $numberOfDislike
    );

    $postC->addPost($post);

    header("Location: listpost.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Post</title>
</head>
<body>

<h2>Ajouter un Post</h2>

<form method="POST" enctype="multipart/form-data">
    <input type="text" name="idCreateur" placeholder="ID Createur" required><br><br>
    <input type="text" name="subject" placeholder="Subject" required><br><br>
    <textarea name="textContent" placeholder="Text Content"></textarea><br><br>
    <input type="file" name="image"><br><br>
    <input type="file" name="video"><br><br>
    <button type="submit">Ajouter</button>
</form>

</body>
</html>
