<?php
require_once '../../../Controleur/commentC.php';

$commentC = new CommentC();
$pageTitle = 'All Comments';
$currentPage = 'comments';

$searchType = trim($_GET['searchType'] ?? '');
$keyword = trim($_GET['keyword'] ?? '');
$comments = ($searchType !== '' || $keyword !== '') ? $commentC->searchCommentsAdmin($searchType, $keyword) : $commentC->listAllCommentsAdmin();
$stats = $commentC->getAdminStats();

require_once '../partials/header.php';
?>
<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card"><div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h3 class="mb-1">Comments Dashboard</h3>
                <p class="text-muted mb-0">Search comments by post ID, comment ID to inspect replies, or by creator.</p>
            </div>
            <a href="../post/index.php" class="btn btn-outline-light"><i class="mdi mdi-arrow-left"></i> Back to Posts</a>
        </div></div>
    </div>
</div>
<div class="row">
    <div class="col-md-4 grid-margin stretch-card"><div class="card admin-stats-card"><div class="card-body"><h6 class="text-muted font-weight-normal">Total Comments</h6><h3 class="mb-0"><?= (int)$stats['totalComments'] ?></h3></div></div></div>
    <div class="col-md-4 grid-margin stretch-card"><div class="card admin-stats-card"><div class="card-body"><h6 class="text-muted font-weight-normal">Comment Likes</h6><h3 class="mb-0"><?= (int)$stats['totalLikes'] ?></h3></div></div></div>
    <div class="col-md-4 grid-margin stretch-card"><div class="card admin-stats-card"><div class="card-body"><h6 class="text-muted font-weight-normal">Comment Dislikes</h6><h3 class="mb-0"><?= (int)$stats['totalDislikes'] ?></h3></div></div></div>
</div>
<div class="row"><div class="col-12 grid-margin stretch-card"><div class="card"><div class="card-body">
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-3"><label class="form-label">Search by</label><select name="searchType" class="form-control"><option value="">All comments</option><option value="postId" <?= $searchType === 'postId' ? 'selected' : '' ?>>Post ID</option><option value="commentId" <?= $searchType === 'commentId' ? 'selected' : '' ?>>Comment ID (show replies)</option><option value="creator" <?= $searchType === 'creator' ? 'selected' : '' ?>>Creator</option></select></div>
        <div class="col-md-7"><label class="form-label">Keyword</label><input type="text" name="keyword" class="form-control" value="<?= htmlspecialchars($keyword) ?>" placeholder="Enter post id, comment id, creator name, or creator id"></div>
        <div class="col-md-2 d-flex align-items-end gap-2"><button type="submit" class="btn btn-info w-100"><i class="mdi mdi-magnify"></i> Search</button><a href="./index.php" class="btn btn-outline-light">Reset</a></div>
    </form>
    <?php if (empty($comments)) : ?>
        <div class="empty-state-admin"><h5>No comments found</h5><p class="text-muted mb-0">Try another search or reset the filters.</p></div>
    <?php else : ?>
        <div class="table-responsive"><table class="table table-dark admin-table"><thead><tr><th>Comment ID</th><th>Target Type</th><th>Target ID</th><th>Creator</th><th>Text</th><th>Sticker</th><th>Image</th><th>Likes</th><th>Dislikes</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($comments as $comment) : ?>
            <tr>
                <td><?= htmlspecialchars($comment['id']) ?></td>
                <td><?= htmlspecialchars($comment['commentedItem']) ?></td>
                <td><?= htmlspecialchars($comment['idCommentedElement']) ?></td>
                <td><?= htmlspecialchars($comment['userName'] ?? ('User #' . $comment['idUser'])) ?></td>
                <td><?= htmlspecialchars(mb_strimwidth((string)($comment['text'] ?? ''), 0, 120, '...')) ?></td>
                <td><?= htmlspecialchars($comment['Sticker'] ?? $comment['sticker'] ?? '') ?></td>
                <td><?php if (!empty($comment['image'])) : ?><img src="../../public/<?= htmlspecialchars($comment['image']) ?>" alt="comment image" style="width:64px;height:64px;object-fit:cover;border-radius:8px;"><?php else : ?><span class="badge badge-secondary">No image</span><?php endif; ?></td>
                <td><?= (int)($comment['numberOfLike'] ?? 0) ?></td>
                <td><?= (int)($comment['numberOfDislike'] ?? 0) ?></td>
                <td>
                    <?php if (($comment['commentedItem'] ?? '') === 'post') : ?><a href="../post/details.php?id=<?= urlencode($comment['idCommentedElement']) ?>" class="admin-action-btn admin-view-btn"><i class="mdi mdi-eye-outline"></i> Post</a><?php endif; ?>
                    <a href="./index.php?searchType=commentId&keyword=<?= urlencode($comment['id']) ?>" class="admin-action-btn admin-view-btn"><i class="mdi mdi-source-branch"></i> Replies</a>
                    <a href="./delete.php?id=<?= urlencode($comment['id']) ?>&searchType=<?= urlencode($searchType) ?>&keyword=<?= urlencode($keyword) ?>" class="admin-action-btn admin-delete-btn js-admin-delete"><i class="mdi mdi-delete-outline"></i> Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>
</div></div></div></div>
<?php require_once '../partials/footer.php'; ?>
