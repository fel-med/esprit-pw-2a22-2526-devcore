<?php
require_once '../../../Controleur/postC.php';
$postC = new PostC();
$posts = $postC->listPosts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
</head>
<body>
    <header>
        <h1>Community Posts</h1>
        <p>Share ideas, content, and engage with the community</p>
    </header>

    <main>
        <section class="posts-feed">
            <h2>Recent Posts</h2>
            <div class="posts-list">
                <?php foreach ($posts as $post) { ?>
                    <div class="post-item">
                        <div class="post-header">
                            <h3><?= htmlspecialchars($post['subject']) ?></h3>
                            <span class="post-author">By creator ID: <?= htmlspecialchars($post['idCreateur']) ?></span>
                            <span class="post-date"><?= htmlspecialchars($post['creationDate']) ?></span>
                        </div>

                        <div class="post-content">
                            <p><?= nl2br(htmlspecialchars($post['textContent'])) ?></p>

                            <div class="post-media">
                                <?php if (!empty($post['imageContent'])) { ?>
                                    <img src="../../public/<?= htmlspecialchars($post['imageContent']) ?>" alt="Post image" class="post-image">
                                <?php } ?>

                                <?php if (!empty($post['VideoContent'])) { ?>
                                    <video controls class="post-video">
                                        <source src="../../public/<?= htmlspecialchars($post['VideoContent']) ?>" type="video/mp4">
                                    </video>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="post-actions">
                            <button class="btn-like">Like (<?= (int)$post['numberOfLike'] ?>)</button>
                            <button class="btn-comment">Comment</button>
                            <span class="views"><?= (int)$post['numberOfView'] ?> views</span>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </section>
    </main>
</body>
</html>
