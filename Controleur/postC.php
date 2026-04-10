<?php
require_once __DIR__ . '/../Modele/post.php';
require_once __DIR__ . '/../config.php';

class PostC
{
    private function generateUuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function showPost($id)
    {
        $sql = "SELECT * FROM post WHERE id = :id";
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute(['id' => $id]);
        return $query->fetch();
    }

    public function listPosts()
    {
        $sql = "SELECT * FROM post ORDER BY creationDate DESC";
        $db = config::getConnexion();
        return $db->query($sql);
    }

    public function deletePost($id)
    {
        $sql = "DELETE FROM post WHERE id = :id";
        $db = config::getConnexion();
        $req = $db->prepare($sql);
        $req->execute(['id' => $id]);
    }

    public function addPost($post)
    {
        $db = config::getConnexion();

        $id = $post->getId();
        if (empty($id)) {
            $id = $this->generateUuid();
            $post->setId($id);
        }

        $sql = "INSERT INTO post (
                    id, idCreateur, subject, creationDate, textContent,
                    imageContent, VideoContent, numberOfView, numberOfLike, numberOfDislike
                ) VALUES (
                    :id, :idCreateur, :subject, :creationDate, :textContent,
                    :imageContent, :VideoContent, :numberOfView, :numberOfLike, :numberOfDislike
                )";

        $query = $db->prepare($sql);
        $query->execute([
            'id' => $post->getId(),
            'idCreateur' => $post->getIdCreateur(),
            'subject' => $post->getSubject(),
            'creationDate' => $post->getCreationDate(),
            'textContent' => $post->getTextContent(),
            'imageContent' => $post->getImageContent(),
            'VideoContent' => $post->getVideoContent(),
            'numberOfView' => $post->getNumberOfView(),
            'numberOfLike' => $post->getNumberOfLike(),
            'numberOfDislike' => $post->getNumberOfDislike()
        ]);
    }

    public function updatePost($post)
    {
        $sql = "UPDATE post SET
                    idCreateur = :idCreateur,
                    subject = :subject,
                    creationDate = :creationDate,
                    textContent = :textContent,
                    imageContent = :imageContent,
                    VideoContent = :VideoContent,
                    numberOfView = :numberOfView,
                    numberOfLike = :numberOfLike,
                    numberOfDislike = :numberOfDislike
                WHERE id = :id";

        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute([
            'id' => $post->getId(),
            'idCreateur' => $post->getIdCreateur(),
            'subject' => $post->getSubject(),
            'creationDate' => $post->getCreationDate(),
            'textContent' => $post->getTextContent(),
            'imageContent' => $post->getImageContent(),
            'VideoContent' => $post->getVideoContent(),
            'numberOfView' => $post->getNumberOfView(),
            'numberOfLike' => $post->getNumberOfLike(),
            'numberOfDislike' => $post->getNumberOfDislike()
        ]);
    }
}
?>