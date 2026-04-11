<?php
require_once '../../../Controleur/postC.php';

$pageTitle = 'My Posts Dashboard';
$postC = new PostC();
$creatorId = 1;

$posts = $postC->listPostsByCreator($creatorId);
$stats = $postC->getCreatorStats($creatorId);

require_once '../partials/header.php';
?>

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h3 class="mb-1">My Posts</h3>
                    <p class="text-muted mb-0">Manage all posts created by creator #1</p>
                </div>
                <a href="./create.php" class="btn btn-success">
                    <i class="mdi mdi-plus-circle-outline"></i> Create New Post
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card stats-card">
            <div class="card-body">
                <h6 class="text-muted font-weight-normal">Total Posts</h6>
                <h3 class="mb-0"><?= (int)$stats['totalPosts'] ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-3 grid-margin stretch-card">
        <div class="card stats-card">
            <div class="card-body">
                <h6 class="text-muted font-weight-normal">Total Views</h6>
                <h3 class="mb-0"><?= (int)$stats['totalViews'] ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-3 grid-margin stretch-card">
        <div class="card stats-card">
            <div class="card-body">
                <h6 class="text-muted font-weight-normal">Total Likes</h6>
                <h3 class="mb-0"><?= (int)$stats['totalLikes'] ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-3 grid-margin stretch-card">
        <div class="card stats-card">
            <div class="card-body">
                <h6 class="text-muted font-weight-normal">Total Dislikes</h6>
                <h3 class="mb-0"><?= (int)$stats['totalDislikes'] ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Posts List</h4>

                <?php if (empty($posts)) : ?>
                    <div class="empty-state">
                        <h5>No posts yet</h5>
                        <p class="text-muted">Create your first post to start filling the dashboard.</p>
                        <a href="./create.php" class="btn btn-primary">Create First Post</a>
                    </div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-dark">
                            <thead>
                                <tr>
                                    <th>Media</th>
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
                                            <img src="../../public/<?= htmlspecialchars($post['imageContent']) ?>" alt="post image" class="post-thumb">
                                        <?php elseif (!empty($post['VideoContent'])) : ?>
                                            <span class="badge badge-info">Video</span>
                                        <?php else : ?>
                                            <span class="badge badge-secondary">No media</span>
                                        <?php endif; ?>
                                    </td>

                                    <td><?= htmlspecialchars($post['subject']) ?></td>

                                    <td>
                                        <div class="post-excerpt">
                                            <?= htmlspecialchars($post['textContent']) ?>
                                        </div>
                                    </td>

                                    <td><?= htmlspecialchars($post['creationDate']) ?></td>
                                    <td><?= (int)$post['numberOfView'] ?></td>
                                    <td><?= (int)$post['numberOfLike'] ?></td>
                                    <td><?= (int)$post['numberOfDislike'] ?></td>

                                    <td class="action-buttons">
                                        <a href="./edit.php?id=<?= urlencode($post['id']) ?>" class="btn btn-warning btn-sm">
                                            <i class="mdi mdi-border-color"></i> Edit
                                        </a>
                                        <a href="./delete.php?id=<?= urlencode($post['id']) ?>"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Are you sure you want to delete this post?');">
                                            <i class="mdi mdi-delete"></i> Delete
                                        </a>
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