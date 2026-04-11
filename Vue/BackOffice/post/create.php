<?php
require_once '../../../Controleur/postC.php';
require_once '../../../Modele/post.php';

$pageTitle = 'Create Post';
$postC = new PostC();
$creatorId = 1;

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $textContent = trim($_POST['textContent'] ?? '');

    $imageContent = null;
    $videoContent = null;

    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
        $imageName = time() . '_img_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['image']['name']));
        $imagePath = '../../public/uploads/' . $imageName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
            $imageContent = 'uploads/' . $imageName;
        }
    }

    if (!empty($_FILES['video']['name']) && $_FILES['video']['error'] === 0) {
        $videoName = time() . '_vid_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['video']['name']));
        $videoPath = '../../public/uploads/' . $videoName;

        if (move_uploaded_file($_FILES['video']['tmp_name'], $videoPath)) {
            $videoContent = 'uploads/' . $videoName;
        }
    }

    if ($subject !== '' && $textContent !== '') {
        $post = new Post(
            null,
            $creatorId,
            $subject,
            date('Y-m-d H:i:s'),
            $textContent,
            $imageContent,
            $videoContent,
            0,
            0,
            0
        );

        $postC->addPost($post);
        header('Location: ./index.php');
        exit();
    } else {
        $errorMessage = 'Please fill all required fields correctly.';
    }
}

require_once '../partials/header.php';
?>

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card post-form-card">
            <div class="card-body">
                <h4 class="card-title">Create New Post</h4>
                <p class="card-description">Fill the form below to publish a new post</p>

                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
                <?php endif; ?>

                <form id="postForm" class="forms-sample" method="POST" enctype="multipart/form-data" novalidate>
                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <input type="text" class="form-control" id="subject" name="subject" placeholder="Enter post subject">
                        <div id="subjectError" class="validation-error"></div>
                    </div>

                    <div class="form-group">
                        <label for="textContent">Content *</label>
                        <textarea class="form-control" id="textContent" name="textContent" rows="6" placeholder="Write your post content here..."></textarea>
                        <div id="textContentError" class="validation-error"></div>
                    </div>

                    <div class="form-group">
                        <label for="image">Image</label>
                        <input type="file" class="form-control" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">
                        <div id="imageError" class="validation-error"></div>
                        <div id="imagePreview"></div>
                    </div>

                    <div class="form-group">
                        <label for="video">Video</label>
                        <input type="file" class="form-control" id="video" name="video" accept=".mp4,.webm,.ogg">
                        <div id="videoError" class="validation-error"></div>
                        <div id="videoPreview"></div>
                    </div>

                    <button type="submit" class="btn btn-primary mr-2">Publish Post</button>
                    <a href="./index.php" class="btn btn-dark">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../partials/footer.php'; ?>