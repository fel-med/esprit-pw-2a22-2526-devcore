<?php
// Controller class for Post
// Handles post-related operations and connects to the Post model

require_once '../Modele/post.php';
require_once '../config.php';

class PostC {
    private $postModel;

    public function __construct() {
        // Instantiate the model
        $this->postModel = new Post();
    }

    public function afficherPage() {
        // Method to display the post page
        // This is a starter method - full logic will be added later
    }

    public function creerObjetExemple() {
        // Example method to create a sample post object
        // This demonstrates the connection between controller and model
        $post = new Post(1, 1, 'Sample Subject', '2023-01-01', 'Text content', 'image.jpg', 'video.mp4', 100);
        return $post;
    }
}
?>