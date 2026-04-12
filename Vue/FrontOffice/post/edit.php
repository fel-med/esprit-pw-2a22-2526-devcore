<?php
require_once '../../../Controleur/postC.php';
require_once '../../../Modele/post.php';

$postC = new PostC();
$creatorId = 1;
$pageTitle = 'Edit Post';
$currentPage = 'portfolio';

function handleUploadedPostFile(string $inputName, array $allowedExtensions, int $maxBytes, string $prefix): ?string
{
    if (empty($_FILES[$inputName]['name']) || $_FILES[$inputName]['error'] !== 0) {
        return null;
    }

    $fileName = $_FILES[$inputName]['name'];
    $fileSize = $_FILES[$inputName]['size'];
    $tmpName = $_FILES[$inputName]['tmp_name'];

    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        return null;
    }

    if ($fileSize > $maxBytes) {
        return null;
    }

    $safeName = time() . '_' . $prefix . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($fileName));
    $destination = '../../public/uploads/' . $safeName;

    if (!move_uploaded_file($tmpName, $destination)) {
        return null;
    }

    return 'uploads/' . $safeName;
}

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

    $newImage = handleUploadedPostFile('image', ['jpg', 'jpeg', 'png', 'webp'], 5 * 1024 * 1024, 'img');
    if ($newImage !== null) {
        $imageContent = $newImage;
    }

    $newVideo = handleUploadedPostFile('video', ['mp4', 'webm', 'ogg'], 30 * 1024 * 1024, 'vid');
    if ($newVideo !== null) {
        $videoContent = $newVideo;
    }

    if ($subject !== '' && mb_strlen($subject) >= 3 && $textContent !== '' && mb_strlen($textContent) >= 10) {
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
        header('Location: ./portfolio.php');
        exit();
    } else {
        $errorMessage = 'Please fill in the required fields correctly.';
    }
}

require_once '../partials/header.php';
?>

<section class="py-5">
    <div class="container px-4 px-lg-5">
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-10">
                <div class="form-shell">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                        <div>
                            <h1 class="h3 fw-bolder mb-1">Edit Post</h1>
                            <p class="text-muted mb-0">Update your publication and keep your creator page fresh.</p>
                        </div>
                        <a href="./portfolio.php" class="btn social-nav-btn">Back to My Space</a>
                    </div>

                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
                    <?php endif; ?>

                    <form id="postForm" method="POST" enctype="multipart/form-data" novalidate>
                        <div class="mb-4">
                            <label for="subject" class="social-label">Subject *</label>
                            <input
                                type="text"
                                class="form-control social-input"
                                id="subject"
                                name="subject"
                                value="<?= htmlspecialchars($post['subject']) ?>"
                                placeholder="Enter a clear and attractive title"
                            >
                            <div id="subjectError" class="validation-error"></div>
                            <div id="subjectCounter" class="input-counter"></div>
                        </div>

                        <div class="mb-4">
                            <label for="textContent" class="social-label">Content *</label>
                            <textarea
                                class="form-control social-textarea"
                                id="textContent"
                                name="textContent"
                                rows="8"
                                placeholder="Write your post content here..."><?= htmlspecialchars($post['textContent']) ?></textarea>
                            <div id="textContentError" class="validation-error"></div>
                            <div id="contentCounter" class="input-counter"></div>
                        </div>

                        <div class="mb-4">
                            <label class="social-label">Current Image</label>
                            <div class="preview-box">
                                <?php if (!empty($post['imageContent'])): ?>
                                    <img src="../../public/<?= htmlspecialchars($post['imageContent']) ?>" alt="Current image">
                                <?php else: ?>
                                    <p class="text-muted mb-0">No image uploaded.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="image" class="social-label">Replace Image</label>
                            <input type="file" class="form-control social-input" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">
                            <div id="imageError" class="validation-error"></div>
                            <div id="imagePreview" class="preview-box"></div>
                        </div>

                        <div class="mb-4">
                            <label class="social-label">Current Video</label>
                            <div class="preview-box">
                                <?php if (!empty($post['VideoContent'])): ?>
                                    <video controls playsinline>
                                        <source src="../../public/<?= htmlspecialchars($post['VideoContent']) ?>">
                                    </video>
                                <?php else: ?>
                                    <p class="text-muted mb-0">No video uploaded.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="video" class="social-label">Replace Video</label>
                            <input type="file" class="form-control social-input" id="video" name="video" accept=".mp4,.webm,.ogg">
                            <div id="videoError" class="validation-error"></div>
                            <div id="videoPreview" class="preview-box"></div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn social-create-btn">Save Changes</button>
                            <a href="./portfolio.php" class="btn social-nav-btn">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once '../partials/footer.php'; ?>