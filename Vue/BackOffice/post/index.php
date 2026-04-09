<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts - Cre8Connect</title>
    <link rel="stylesheet" href="../css/backoffice.css">
</head>
<body>
    <header>
        <h1>Community Posts</h1>
        <p>Share ideas, content, and engage with the community</p>
    </header>

    <main>
        <section class="create-post-section">
            <h2>Create New Post</h2>
            <div class="create-post-form">
                <form action="#" method="post">
                    <div class="form-group">
                        <label for="subject">Subject:</label>
                        <input type="text" id="subject" name="subject" placeholder="Enter post subject">
                    </div>
                    <div class="form-group">
                        <label for="content">Content:</label>
                        <textarea id="content" name="content" placeholder="Write your post content here..."></textarea>
                    </div>
                    <div class="form-group">
                        <label for="image">Image:</label>
                        <input type="file" id="image" name="image" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label for="video">Video:</label>
                        <input type="file" id="video" name="video" accept="video/*">
                    </div>
                    <button type="submit" class="btn-post">Publish Post</button>
                </form>
            </div>
        </section>

        <section class="posts-feed">
            <h2>Recent Posts</h2>
            <div class="posts-list">
                <div class="post-item">
                    <div class="post-header">
                        <h3>Sample Post Subject</h3>
                        <span class="post-author">By: User1</span>
                        <span class="post-date">Jan 1, 2023</span>
                    </div>
                    <div class="post-content">
                        <p>This is sample post content. It can include text, images, and videos to engage the community.</p>
                        <div class="post-media">
                            <img src="../../public/images/sample.jpg" alt="Sample image" class="post-image">
                        </div>
                    </div>
                    <div class="post-actions">
                        <button class="btn-like">Like (10)</button>
                        <button class="btn-comment">Comment (5)</button>
                        <button class="btn-share">Share</button>
                        <span class="views">100 views</span>
                    </div>
                </div>
                <div class="post-item">
                    <div class="post-header">
                        <h3>Another Interesting Post</h3>
                        <span class="post-author">By: User2</span>
                        <span class="post-date">Dec 28, 2022</span>
                    </div>
                    <div class="post-content">
                        <p>Another sample post with different content and media.</p>
                        <div class="post-media">
                            <video controls class="post-video">
                                <source src="../../public/uploads/sample.mp4" type="video/mp4">
                            </video>
                        </div>
                    </div>
                    <div class="post-actions">
                        <button class="btn-like">Like (8)</button>
                        <button class="btn-comment">Comment (3)</button>
                        <button class="btn-share">Share</button>
                        <span class="views">75 views</span>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2023 Cre8Connect. All rights reserved.</p>
    </footer>
</body>
</html>