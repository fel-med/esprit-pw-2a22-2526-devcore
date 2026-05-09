<?php
require_once __DIR__ . '/../layout/early-theme.php';
require_once '../../../Controleur/session_helper.php';
cc_start_session();
cc_require_admin('../../FrontOffice/utilisateur/login.php');
require_once '../../../Controleur/postC.php';
require_once '../../../Controleur/commentC.php';

$postC = new PostC();
$commentC = new CommentC();
$pageTitle = 'Post Details';
$currentPage = 'posts';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Post ID is missing.');
}

$postId = $_GET['id'];
$post = $postC->showPost($postId);
if (!$post) {
    die('Post not found.');
}

$comments = $commentC->searchCommentsAdmin('postId', $postId);

function postDetailAssetVersion($path) {
    return is_file($path) ? '?v=' . urlencode((string) filemtime($path)) : '';
}
if (!headers_sent()) header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php cre8_bo_early_theme_print_head_script(); ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Post Details — Admin · Cre8Connect</title>
<link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/backoffice.css<?= postDetailAssetVersion(__DIR__ . '/../css/backoffice.css') ?>">
<link rel="stylesheet" href="../layout/back-layout.css<?= postDetailAssetVersion(__DIR__ . '/../layout/back-layout.css') ?>">
<link rel="stylesheet" href="../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css<?= postDetailAssetVersion(__DIR__ . '/../utilisateur/assets/vendors/mdi/css/materialdesignicons.min.css') ?>">
<link rel="stylesheet" href="../css/new_style_backoffice.css<?= postDetailAssetVersion(__DIR__ . '/../css/new_style_backoffice.css') ?>">
<style>
.post-admin {
    --post-bg-card: #16213e;
    --post-bg-card-2: #0f3460;
    --post-border: rgba(139, 92, 246, 0.22);
    --post-border-light: rgba(255, 255, 255, 0.08);
    --post-text: #e2e8f0;
    --post-muted: #94a3b8;
    --post-label: #a78bfa;
    --post-primary: #8b5cf6;
    --post-accent: #ec4899;
    --post-accent-light: #f9a8d4;
    --post-shadow: 0 4px 24px rgba(139, 92, 246, 0.12);
    color: var(--post-text);
}
body.light-mode .post-admin {
    --post-bg-card: #ffffff;
    --post-bg-card-2: #f8f5ff;
    --post-border: rgba(139, 92, 246, 0.18);
    --post-border-light: rgba(139, 92, 246, 0.10);
    --post-text: #1e1b4b;
    --post-muted: #6b7280;
    --post-label: #7c3aed;
    --post-shadow: 0 2px 16px rgba(139, 92, 246, 0.10);
}
.post-admin .card {
    background: var(--post-bg-card) !important;
    border: 1px solid var(--post-border) !important;
    border-radius: 16px !important;
    box-shadow: var(--post-shadow);
    color: var(--post-text) !important;
}
.post-admin .text-muted { color: var(--post-muted) !important; }
.post-admin .text-light { color: var(--post-text) !important; }
.post-admin .post-detail-card { border-radius: 20px !important; }
.post-admin .post-detail-media {
    width: 100%;
    max-height: 500px;
    object-fit: contain;
    background: #000;
    border-radius: 16px;
    box-shadow: 0 8px 28px rgba(139,92,246,0.14);
}
.post-admin .post-creator-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--post-muted) !important;
    font-weight: 600;
    font-size: 13px;
}
.post-admin .post-creator-badge i { color: var(--post-primary); font-size: 17px; }
.post-admin .detail-meta-badges { display:flex; flex-wrap:wrap; gap:10px; margin-top:1rem; }
.post-admin .detail-meta-badge {
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:.48rem .85rem;
    border-radius:999px;
    background:rgba(139,92,246,.10);
    color:var(--post-label);
    font-weight:600;
    font-size:12px;
    border:1px solid var(--post-border);
}
.post-admin .table-dark,
.post-admin .admin-table {
    background-color: var(--post-bg-card) !important;
    color: var(--post-text) !important;
    border-color: var(--post-border) !important;
}
.post-admin .table-dark thead th,
.post-admin .admin-table thead th {
    background: var(--post-bg-card-2) !important;
    color: var(--post-label) !important;
    border-color: var(--post-border) !important;
    font-weight:700;
    font-size:11px;
    letter-spacing:.06em;
    text-transform:uppercase;
}
.post-admin .table-dark td,
.post-admin .table-dark th,
.post-admin .admin-table td,
.post-admin .admin-table th {
    border-color: var(--post-border-light) !important;
    color: var(--post-text) !important;
    vertical-align: middle;
}
.post-admin .admin-action-btn {
    display:inline-flex;
    align-items:center;
    gap:5px;
    border-radius:999px;
    padding:.45rem .9rem;
    font-weight:700;
    font-size:12px;
    text-decoration:none;
    margin-right:5px;
    margin-bottom:5px;
    transition:all .18s ease;
}
.post-admin .admin-action-btn:hover { transform: translateY(-2px); text-decoration:none; }
.post-admin .admin-view-btn { background:rgba(139,92,246,.15); color:var(--post-label) !important; border:1.5px solid rgba(139,92,246,.35); }
.post-admin .admin-delete-btn { background:rgba(236,72,153,.12); color:var(--post-accent-light) !important; border:1.5px solid rgba(236,72,153,.28); }
.post-admin .btn { border-radius:999px; font-weight:700; }
.post-admin .btn-info { background:linear-gradient(135deg,#0ea5e9,#0284c7) !important; border-color:transparent !important; color:#fff !important; }
.post-admin .btn-danger { background:linear-gradient(135deg,#ec4899,#be185d) !important; border-color:transparent !important; color:#fff !important; }
.post-admin .btn-outline-light { border-color:var(--post-border) !important; color:var(--post-muted) !important; }
.post-admin hr { border-color: var(--post-border); opacity: 1; }
</style>
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/logo.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/logo.png">
<link rel="apple-touch-icon" href="../../public/images/logo.png">
</head>
<body class="cre8-admin-layout"><?php cre8_bo_early_theme_print_body_script(); ?>
<div class="container-scroller cre8-admin-page">
<?php
$backActive = 'posts';
require_once __DIR__ . '/../layout/sidebar.php';
?>
<div class="container-fluid page-body-wrapper cre8-admin-main">
<?php require_once __DIR__ . '/../layout/header.php'; ?>
    <div class="main-panel">
    <div class="content-wrapper">
        <div class="post-admin">

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card post-detail-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                    <div>
                        <h3 class="mb-1">Post Details</h3>
                        <p class="text-muted mb-0">Review complete content before moderation action.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="./index.php" class="btn btn-outline-light"><i class="mdi mdi-arrow-left"></i> Back</a>
                        <a href="../comment/index.php?searchType=postId&keyword=<?= urlencode($postId) ?>" class="btn btn-info"><i class="mdi mdi-comment-outline"></i> Manage Comments</a>
                        <a href="./delete.php?id=<?= urlencode($post['id']) ?>" class="btn btn-danger js-admin-delete"><i class="mdi mdi-delete-outline"></i> Delete Post</a>
                    </div>
                </div>

                <div class="mb-4">
                    <h2 class="mb-2"><?= htmlspecialchars($post['subject']) ?></h2>
                    <div class="post-creator-badge"><i class="mdi mdi-account-circle"></i> <?= htmlspecialchars($post['creatorName'] ?? ('Creator #' . $post['idCreateur'])) ?></div>
                    <div class="detail-meta-badges">
                        <span class="detail-meta-badge"><i class="mdi mdi-calendar-clock"></i> <?= htmlspecialchars($post['creationDate']) ?></span>
                        <span class="detail-meta-badge"><i class="mdi mdi-eye"></i> <?= (int)$post['numberOfView'] ?> views</span>
                        <span class="detail-meta-badge"><i class="mdi mdi-thumb-up-outline"></i> <?= (int)$post['numberOfLike'] ?> likes</span>
                        <span class="detail-meta-badge"><i class="mdi mdi-thumb-down-outline"></i> <?= (int)$post['numberOfDislike'] ?> dislikes</span>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="mb-3">Post content</h5>
                    <p class="text-light" style="white-space: pre-line;"><?= htmlspecialchars($post['textContent']) ?></p>
                </div>

                <?php if (!empty($post['imageContent'])) : ?>
                    <div class="mb-4"><h5 class="mb-3">Image</h5><img src="../../public/<?= htmlspecialchars($post['imageContent']) ?>" alt="Post image" class="post-detail-media"></div>
                <?php endif; ?>
                <?php if (!empty($post['VideoContent'])) : ?>
                    <div class="mb-4"><h5 class="mb-3">Video</h5><video class="post-detail-media" controls preload="metadata" playsinline><source src="../../public/<?= htmlspecialchars($post['VideoContent']) ?>"></video></div>
                <?php endif; ?>

                <hr>
                <h4 class="mb-3">Comments linked to this post</h4>
                <?php if (empty($comments)) : ?>
                    <p class="text-muted mb-0">No comments on this post yet.</p>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-dark admin-table">
                            <thead>
                                <tr>
                                    <th>Comment ID</th>
                                    <th>Type</th>
                                    <th>User</th>
                                    <th>Text</th>
                                    <th>Likes</th>
                                    <th>Dislikes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($comments as $comment) : ?>
                                    <tr>
                                        <td><?= htmlspecialchars($comment['id']) ?></td>
                                        <td><?= htmlspecialchars($comment['commentedItem']) ?></td>
                                        <td><?= htmlspecialchars($comment['userName'] ?? ('User #' . $comment['idUser'])) ?></td>
                                        <td><?= htmlspecialchars($comment['text'] ?? '') ?></td>
                                        <td><?= (int)($comment['numberOfLike'] ?? 0) ?></td>
                                        <td><?= (int)($comment['numberOfDislike'] ?? 0) ?></td>
                                        <td>
                                            <a href="../comment/index.php?searchType=commentId&keyword=<?= urlencode($comment['id']) ?>" class="admin-action-btn admin-view-btn"><i class="mdi mdi-source-branch"></i> Replies</a>
                                            <a href="../comment/delete.php?id=<?= urlencode($comment['id']) ?>&redirect=post&postId=<?= urlencode($postId) ?>" class="admin-action-btn admin-delete-btn js-admin-delete"><i class="mdi mdi-delete-outline"></i> Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

        </div>
    </div>
    </div>
</div>
</div>
<script src="../layout/back-layout.js<?= postDetailAssetVersion(__DIR__ . '/../layout/back-layout.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-admin-delete').forEach(function (button) {
        button.addEventListener('click', function (e) {
            if (!confirm('Are you sure you want to delete this post?')) {
                e.preventDefault();
            }
        });
    });
    const videos = document.querySelectorAll('video');
    videos.forEach(function (videoEl) {
        videoEl.addEventListener('play', function () {
            videos.forEach(function (other) {
                if (other !== videoEl) other.pause();
            });
        });
    });
});
</script>
</body>
</html>
