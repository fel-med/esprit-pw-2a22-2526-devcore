<?php
require_once '../../../Controleur/postC.php';

$pageTitle = 'Community Posts';
$postC = new PostC();

$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'recent';

if ($search !== '') {
    $posts = $postC->searchPosts($search);
} elseif ($sort === 'trending') {
    $posts = $postC->listTrendingPosts();
} else {
    $posts = $postC->listPosts();
}

require_once '../partials/header.php';
?>

<header class="py-5 post-hero">
    <div class="container px-5">
        <div class="text-center">
            <h1 class="display-5 fw-bolder mb-3">Community Posts Feed</h1>
            <p class="lead fw-light text-muted mb-4">
                Browse all user posts, search by creator or subject, and explore trending content.
            </p>
        </div>

        <div class="card search-toolbar shadow-sm border-0">
            <div class="card-body">
                <form method="GET" action="./index.php" class="row g-3 align-items-center">
                    <div class="col-md-8">
                        <input
                            type="text"
                            id="searchInput"
                            name="search"
                            class="form-control form-control-lg"
                            placeholder="Search by creator id or subject..."
                            value="<?= htmlspecialchars($search) ?>"
                        >
                    </div>

                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-search"></i> Search
                        </button>

                        <a href="./index.php?sort=trending" class="btn btn-outline-dark btn-lg">
                            <i class="bi bi-fire"></i> Trending
                        </a>

                        <a href="./index.php" id="clearSearchBtn" class="btn btn-outline-secondary btn-lg" style="<?= $search !== '' ? '' : 'display:none;' ?>">
                            Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</header>

<section class="py-5">
    <div class="container px-5">
        <?php if (empty($posts)) : ?>
            <div class="feed-empty">
                <h4>No posts found</h4>
                <p class="text-muted mb-0">Try another keyword or switch back to the full feed.</p>
            </div>
        <?php else : ?>
            <div class="row g-4">
                <?php foreach ($posts as $post) : ?>
                    <div class="col-lg-6">
                        <div class="card post-card h-100">
                            <?php if (!empty($post['imageContent'])) : ?>
                                <img src="../../public/<?= htmlspecialchars($post['imageContent']) ?>" class="post-media" alt="Post image">
                            <?php elseif (!empty($post['VideoContent'])) : ?>
                                <video class="post-video" controls>
                                    <source src="../../public/<?= htmlspecialchars($post['VideoContent']) ?>">
                                </video>
                            <?php endif; ?>

                            <div class="card-body p-4">
                                <div class="post-meta mb-2">
                                    Creator #<?= htmlspecialchars($post['idCreateur']) ?> • <?= htmlspecialchars($post['creationDate']) ?>
                                </div>

                                <h3 class="h4 fw-bolder mb-3"><?= htmlspecialchars($post['subject']) ?></h3>

                                <p class="post-content text-muted mb-4">
                                    <?= htmlspecialchars(mb_strimwidth($post['textContent'], 0, 240, '...')) ?>
                                </p>

                                <div class="d-flex flex-wrap gap-2 mb-4">
                                    <span class="stat-badge"><i class="bi bi-eye"></i> <?= (int)$post['numberOfView'] ?></span>
                                    <span class="stat-badge"><i class="bi bi-hand-thumbs-up"></i> <?= (int)$post['numberOfLike'] ?></span>
                                    <span class="stat-badge"><i class="bi bi-hand-thumbs-down"></i> <?= (int)$post['numberOfDislike'] ?></span>
                                </div>

                                <div class="post-actions">
                                    <a href="./details.php?id=<?= urlencode($post['id']) ?>" class="btn btn-primary">
                                        Read more
                                    </a>

                                    <a href="./like.php?id=<?= urlencode($post['id']) ?>" class="btn btn-outline-success">
                                        <i class="bi bi-hand-thumbs-up"></i> Like
                                    </a>

                                    <a href="./dislike.php?id=<?= urlencode($post['id']) ?>" class="btn btn-outline-danger">
                                        <i class="bi bi-hand-thumbs-down"></i> Dislike
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once '../partials/footer.php'; ?>