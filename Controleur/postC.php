<?php

require_once __DIR__ . '/../Modele/post.php';
require_once __DIR__ . '/../config.php';

class PostC
{
    private PDO $db;

    public function __construct()
    {
        $this->db = config::getConnexion();
    }

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

    public function showPost(string $id)
    {
        $sql = "SELECT * FROM post WHERE id = :id";
        $query = $this->db->prepare($sql);
        $query->execute(['id' => $id]);
        return $query->fetch();
    }

    public function listPosts()
    {
        $sql = "SELECT * FROM post ORDER BY creationDate DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function listPostsByCreator(int $idCreateur)
    {
        $sql = "SELECT * FROM post WHERE idCreateur = :idCreateur ORDER BY creationDate DESC";
        $query = $this->db->prepare($sql);
        $query->execute(['idCreateur' => $idCreateur]);
        return $query->fetchAll();
    }

    public function searchPosts(string $keyword)
    {
        $sql = "SELECT * FROM post
                WHERE CAST(idCreateur AS CHAR) LIKE :keyword
                   OR subject LIKE :keyword
                ORDER BY creationDate DESC";
        $query = $this->db->prepare($sql);
        $query->execute([
            'keyword' => '%' . $keyword . '%'
        ]);
        return $query->fetchAll();
    }

    public function listTrendingPosts()
    {
        $sql = "SELECT * FROM post ORDER BY numberOfView DESC, creationDate DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function addPost(Post $post)
    {
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

        $query = $this->db->prepare($sql);
        return $query->execute([
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

    public function updatePost(Post $post)
    {
        $sql = "UPDATE post SET
                    subject = :subject,
                    textContent = :textContent,
                    imageContent = :imageContent,
                    VideoContent = :VideoContent
                WHERE id = :id AND idCreateur = :idCreateur";

        $query = $this->db->prepare($sql);
        return $query->execute([
            'id' => $post->getId(),
            'idCreateur' => $post->getIdCreateur(),
            'subject' => $post->getSubject(),
            'textContent' => $post->getTextContent(),
            'imageContent' => $post->getImageContent(),
            'VideoContent' => $post->getVideoContent()
        ]);
    }

    public function deletePost(string $id, int $idCreateur)
    {
        $sql = "DELETE FROM post WHERE id = :id AND idCreateur = :idCreateur";
        $query = $this->db->prepare($sql);
        return $query->execute([
            'id' => $id,
            'idCreateur' => $idCreateur
        ]);
    }

    public function incrementViews(string $id)
    {
        $sql = "UPDATE post SET numberOfView = numberOfView + 1 WHERE id = :id";
        $query = $this->db->prepare($sql);
        return $query->execute(['id' => $id]);
    }

    public function incrementLike(string $id)
    {
        $sql = "UPDATE post SET numberOfLike = numberOfLike + 1 WHERE id = :id";
        $query = $this->db->prepare($sql);
        return $query->execute(['id' => $id]);
    }

    public function incrementDislike(string $id)
    {
        $sql = "UPDATE post SET numberOfDislike = numberOfDislike + 1 WHERE id = :id";
        $query = $this->db->prepare($sql);
        return $query->execute(['id' => $id]);
    }

    public function creatorOwnsPost(string $id, int $idCreateur): bool
    {
        $sql = "SELECT COUNT(*) as total FROM post WHERE id = :id AND idCreateur = :idCreateur";
        $query = $this->db->prepare($sql);
        $query->execute([
            'id' => $id,
            'idCreateur' => $idCreateur
        ]);
        $result = $query->fetch();
        return (int)$result['total'] > 0;
    }

    public function getCreatorStats(int $idCreateur)
    {
        $sql = "SELECT 
                    COUNT(*) as totalPosts,
                    COALESCE(SUM(numberOfView), 0) as totalViews,
                    COALESCE(SUM(numberOfLike), 0) as totalLikes,
                    COALESCE(SUM(numberOfDislike), 0) as totalDislikes
                FROM post
                WHERE idCreateur = :idCreateur";
        $query = $this->db->prepare($sql);
        $query->execute(['idCreateur' => $idCreateur]);
        return $query->fetch();
    }
}