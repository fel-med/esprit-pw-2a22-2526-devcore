<?php
require_once '../../../Controleur/session_helper.php';
cc_start_session();
require_once '../../../Controleur/postC.php';
require_once '../../../Modele/post.php';
date_default_timezone_set('Africa/Tunis');

// ── VÉRIFICATION SESSION ──────────────────────────────────────
cc_require_login('../utilisateur/login.php');

$postC = new PostC();
$creatorId = (int)$_SESSION['id'];
$pageTitle = 'Create Post';
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

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $textContent = trim($_POST['textContent'] ?? '');

    $imageContent = handleUploadedPostFile('image', ['jpg', 'jpeg', 'png', 'webp'], 5 * 1024 * 1024, 'img');
    $videoContent = handleUploadedPostFile('video', ['mp4', 'webm', 'ogg'], 200 * 1024 * 1024, 'vid');

    if ($subject !== '' && mb_strlen($subject) >= 3 && $textContent !== '' && mb_strlen($textContent) >= 10) {
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
        header('Location: ./portfolio.php');
        exit();
    } else {
        $errorMessage = 'Please fill in the required fields correctly.';
    }
}

$frontActive = 'myspace';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/../layout/front-theme-bootstrap.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=DM+Sans:wght@400;500;700;800&family=Fraunces:wght@700;800&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="../assets/css/styles.css" rel="stylesheet" />
    <link href="../layout/front-header.css" rel="stylesheet" />
    <link href="../assets/post-front.css?v=3" rel="stylesheet" />
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/logo.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/logo.png">
<link rel="apple-touch-icon" href="../../public/images/logo.png">
</head>
<body class="d-flex flex-column min-vh-100 social-body">
<main class="flex-shrink-0">
<?php require_once __DIR__ . '/../layout/header.php'; ?>

<section class="py-5">
    <div class="container px-4 px-lg-5">
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-10">
                <div class="form-shell">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                        <div>
                            <h1 class="h3 fw-bolder mb-1">Create a new post</h1>
                            <p class="text-muted mb-0">Share your latest update with your audience.</p>
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
                                placeholder="Enter a clear and attractive title"
                                value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>"
                            >
                            <div id="subjectError" class="validation-error"></div>
                            <div id="subjectCounter" class="input-counter"></div>
                        </div>

                        <div class="mb-4 p-3 rounded-4 border bg-light-subtle">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <div>
                                    <label for="aiBrief" class="social-label mb-1">Generate content with AI</label>
                                    <p class="text-muted small mb-0">Describe the idea, choose a style, and let AI fill the content field for you.</p>
                                </div>
                                <span class="badge text-bg-light border">Text + optional image</span>
                            </div>

                            <div class="mb-3">
                                <label for="aiBrief" class="social-label">Describe your idea *</label>
                                <textarea
                                    class="form-control social-textarea"
                                    id="aiBrief"
                                    name="aiBrief"
                                    rows="3"
                                    placeholder="Example: this post talks about my last 3D artwork of a president holding people upon his head."><?= htmlspecialchars($_POST['aiBrief'] ?? '') ?></textarea>
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
                                        value="<?= htmlspecialchars($_POST['aiStyle'] ?? '') ?>"
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
                                        value="<?= htmlspecialchars($_POST['aiSentenceCount'] ?? '4') ?>"
                                    >
                                </div>
                                <div class="col-md-3">
                                    <button type="button" class="btn social-create-btn w-100 js-ai-generate" data-ai-mode="generate">Generate</button>
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
                                placeholder="Write your post content here..."><?= htmlspecialchars($_POST['textContent'] ?? '') ?></textarea>
                            <div id="textContentError" class="validation-error"></div>
                            <div id="contentCounter" class="input-counter"></div>
                        </div>

                        <div class="mb-4">
                            <label for="image" class="social-label">Image</label>
                            <input type="file" class="form-control social-input" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">
                            <div class="form-text">If you generate content with AI after choosing an image, the image will be used as extra context.</div>
                            <div id="imageError" class="validation-error"></div>
                            <div id="imagePreview" class="preview-box"></div>
                        </div>

                        <div class="mb-4">
                            <label for="video" class="social-label">Video</label>
                            <input type="file" class="form-control social-input" id="video" name="video" accept=".mp4,.webm,.ogg">
                            <div id="videoError" class="validation-error"></div>
                            <div id="videoPreview" class="preview-box"></div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn social-create-btn">Publish Post</button>
                            <a href="./portfolio.php" class="btn social-nav-btn">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="../layout/front-header.js"></script>
<?php require_once '../partials/footer.php'; ?>
