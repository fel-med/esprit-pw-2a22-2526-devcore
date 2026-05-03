<?php
require_once '../../../Controleur/postC.php';
require_once '../../../Modele/post.php';

$postC = new PostC();
$creatorId = 1;
$pageTitle = 'Edit Post';
$currentPage = 'portfolio';

function handleUploadedPostFile(string $inputName, array $allowedExtensions, int $maxBytes, string $prefix): ?string
{
    if (empty($_FILES[$inputName]['name'])) {
        return null;
    }
    if ($_FILES[$inputName]['error'] !== 0) {
        error_log("Erreur upload '$inputName': code " . $_FILES[$inputName]['error']);
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

    $newVideo = handleUploadedPostFile('video', ['mp4', 'webm', 'ogg'], 100 * 1024 * 1024, 'vid');
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
                        <input type="hidden" id="existingImagePath" name="existingImagePath" value="<?= htmlspecialchars($post['imageContent'] ?? '') ?>">

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

                        <div class="mb-4 p-3 rounded-4 border bg-light-subtle">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <div>
                                    <label for="aiBrief" class="social-label mb-1">Enhance content with AI</label>
                                    <p class="text-muted small mb-0">Describe what you want to improve. The AI will rewrite the content field without changing the rest of your form.</p>
                                </div>
                                <span class="badge text-bg-light border">Enhance current text</span>
                            </div>

                            <div class="mb-3">
                                <label for="aiBrief" class="social-label">Describe your idea *</label>
                                <textarea
                                    class="form-control social-textarea"
                                    id="aiBrief"
                                    name="aiBrief"
                                    rows="3"
                                    placeholder="Example: make the text more artistic and emotional, and mention the symbolic meaning of the artwork."></textarea>
                            </div>

                            <div class="row g-3 align-items-end">
                                <div class="col-md-6">
                                    <label for="aiStyle" class="social-label">Style</label>
                                    <input
                                        type="text"
                                        class="form-control social-input"
                                        id="aiStyle"
                                        name="aiStyle"
                                        placeholder="Artistic, emotional, professional, storytelling..."
                                    >
                                </div>
                                <div class="col-md-3">
                                    <label for="aiSentenceCount" class="social-label">Number of phrases</label>
                                    <input
                                        type="number"
                                        class="form-control social-input"
                                        id="aiSentenceCount"
                                        name="aiSentenceCount"
                                        min="1"
                                        max="12"
                                        value="4"
                                    >
                                </div>
                                <div class="col-md-3">
                                    <button type="button" class="btn social-create-btn w-100 js-ai-generate" data-ai-mode="enhance">Enhance</button>
                                </div>
                            </div>

                            <div class="ai-status small mt-3" aria-live="polite"></div>
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
                            <div class="form-text">If you click Enhance after choosing a new image, the AI will use the new image first. Otherwise it will use the current post image if one exists.</div>
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
