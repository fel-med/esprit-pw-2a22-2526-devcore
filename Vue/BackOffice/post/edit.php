<?php
require_once '../../../Controleur/postC.php';
require_once '../../../Modele/post.php';

$pageTitle = 'Edit Post';
$postC = new PostC();
$creatorId = 1;

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Post ID is missing.');
}

$postId = $_GET['id'];

if (!$postC->creatorOwnsPost($postId, $creatorId)) {
    die('Access denied. This post does not belong to creator #1.');
}

$post = $postC->showPost($postId);

if (!$post) {
    die('Post not found.');
}

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $textContent = trim($_POST['textContent'] ?? '');

    $imageContent = $post['imageContent'];
    $videoContent = $post['VideoContent'];

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
        $updatedPost = new Post(
            $postId,
            $creatorId,
            $subject,
            $post['creationDate'],
            $textContent,
            $imageContent,
            $videoContent,
            $post['numberOfView'],
            $post['numberOfLike'],
            $post['numberOfDislike']
        );

        $postC->updatePost($updatedPost);
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
                <h4 class="card-title">Edit Post</h4>
                <p class="card-description">Update the content of your existing post</p>

                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
                <?php endif; ?>

                <form id="postForm" class="forms-sample" method="POST" enctype="multipart/form-data" novalidate>
                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <input type="text" class="form-control" id="subject" name="subject"
                               value="<?= htmlspecialchars($post['subject']) ?>"
                               placeholder="Enter post subject">
                        <div id="subjectError" class="validation-error"></div>
                    </div>

                    <div class="form-group">
                        <label for="textContent">Content *</label>
                        <textarea class="form-control" id="textContent" name="textContent" rows="6"
                                  placeholder="Write your post content here..."><?= htmlspecialchars($post['textContent']) ?></textarea>
                        <div id="textContentError" class="validation-error"></div>
                    </div>

                    <div class="form-group">
                        <label>Current Image</label><br>
                        <?php if (!empty($post['imageContent'])): ?>
                            <img src="../../public/<?= htmlspecialchars($post['imageContent']) ?>" class="media-preview" style="max-height: 180px;">
                        <?php else: ?>
                            <span class="text-muted">No image uploaded</span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="image">Replace Image</label>
                        <input type="file" class="form-control" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">
                        <div id="imageError" class="validation-error"></div>
                        <div id="imagePreview"></div>
                    </div>

                    <div class="form-group">
                        <label>Current Video</label><br>
                        <?php if (!empty($post['VideoContent'])): ?>
                            <video class="media-preview" controls style="max-height: 220px;">
                                <source src="../../public/<?= htmlspecialchars($post['VideoContent']) ?>">
                            </video>
                        <?php else: ?>
                            <span class="text-muted">No video uploaded</span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="video">Replace Video</label>
                        <input type="file" class="form-control" id="video" name="video" accept=".mp4,.webm,.ogg">
                        <div id="videoError" class="validation-error"></div>
                        <div id="videoPreview"></div>
                    </div>

                    <button type="submit" class="btn btn-warning mr-2">Update Post</button>
                    <a href="./index.php" class="btn btn-dark">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../partials/footer.php'; ?>