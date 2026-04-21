<?php
require_once '../../../Controleur/postC.php';

$postC = new PostC();
$pageTitle = 'All Posts';
$currentPage = 'posts';

$posts = $postC->listPosts();
$stats = $postC->getAdminStats();

require_once '../partials/header.php';
?>

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h3 class="mb-1">All Posts Dashboard</h3>
                    <p class="text-muted mb-0">View all creators' posts, inspect details, review comments, and remove inappropriate content.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="../comment/index.php" class="btn btn-info"><i class="mdi mdi-comment-multiple-outline"></i> Manage Comments</a>
                    <a href="../../FrontOffice/post/index.php" class="btn btn-success"><i class="mdi mdi-open-in-new"></i> Open Actuality</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3 grid-margin stretch-card"><div class="card admin-stats-card"><div class="card-body"><h6 class="text-muted font-weight-normal">Total Posts</h6><h3 class="mb-0"><?= (int)$stats['totalPosts'] ?></h3></div></div></div>
    <div class="col-md-3 grid-margin stretch-card"><div class="card admin-stats-card"><div class="card-body"><h6 class="text-muted font-weight-normal">Total Views</h6><h3 class="mb-0"><?= (int)$stats['totalViews'] ?></h3></div></div></div>
    <div class="col-md-3 grid-margin stretch-card"><div class="card admin-stats-card"><div class="card-body"><h6 class="text-muted font-weight-normal">Total Likes</h6><h3 class="mb-0"><?= (int)$stats['totalLikes'] ?></h3></div></div></div>
    <div class="col-md-3 grid-margin stretch-card"><div class="card admin-stats-card"><div class="card-body"><h6 class="text-muted font-weight-normal">Total Dislikes</h6><h3 class="mb-0"><?= (int)$stats['totalDislikes'] ?></h3></div></div></div>
</div>

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Posts moderation list</h4>

                <?php if (empty($posts)) : ?>
                    <div class="empty-state-admin">
                        <h5>No posts available</h5>
                        <p class="text-muted mb-0">No creator has published a post yet.</p>
                    </div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-dark admin-table">
                            <thead>
                                <tr>
                                    <th>Media</th>
                                    <th>Creator</th>
                                    <th>Subject</th>
                                    <th>Content</th>
                                    <th>Date</th>
                                    <th>Views</th>
                                    <th>Likes</th>
                                    <th>Dislikes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($posts as $post) : ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($post['imageContent'])) : ?>
                                            <img src="../../public/<?= htmlspecialchars($post['imageContent']) ?>" alt="Post image" class="admin-media-thumb">
                                        <?php elseif (!empty($post['VideoContent'])) : ?>
                                            <video class="admin-video-thumb" muted><source src="../../public/<?= htmlspecialchars($post['VideoContent']) ?>"></video>
                                        <?php else : ?>
                                            <span class="badge badge-secondary">No media</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><div class="post-creator-badge"><i class="mdi mdi-account-circle"></i> <?= htmlspecialchars($post['creatorName'] ?? ('Creator #' . $post['idCreateur'])) ?></div></td>
                                    <td><?= htmlspecialchars($post['subject']) ?></td>
                                    <td><div class="post-excerpt"><?= htmlspecialchars($post['textContent']) ?></div></td>
                                    <td><?= htmlspecialchars($post['creationDate']) ?></td>
                                    <td><?= (int)$post['numberOfView'] ?></td>
                                    <td><?= (int)$post['numberOfLike'] ?></td>
                                    <td><?= (int)$post['numberOfDislike'] ?></td>
                                    <td>
                                        <a href="./details.php?id=<?= urlencode($post['id']) ?>" class="admin-action-btn admin-view-btn"><i class="mdi mdi-eye-outline"></i> View</a>
                                        <a href="../comment/index.php?searchType=postId&keyword=<?= urlencode($post['id']) ?>" class="admin-action-btn admin-view-btn"><i class="mdi mdi-comment-outline"></i> Comments</a>
                                        <a href="./delete.php?id=<?= urlencode($post['id']) ?>" class="admin-action-btn admin-delete-btn js-admin-delete"><i class="mdi mdi-delete-outline"></i> Delete</a>
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

<?php require_once '../partials/footer.php'; ?>
