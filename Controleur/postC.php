<?php

require_once __DIR__ . '/../Modele/post.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/commentC.php';

class PostC
{
    private PDO $db;
    private CommentC $commentC;

    public function __construct()
    {
        $this->db = config::getConnexion();
        $this->commentC = new CommentC();
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
        $sql = "SELECT p.*, u.nom AS creatorName
                FROM post p
                INNER JOIN utilisateur u ON u.id = p.idCreateur
                WHERE p.id = :id";
        $query = $this->db->prepare($sql);
        $query->execute(['id' => $id]);
        return $query->fetch();
    }

    public function listPosts()
    {
        $sql = "SELECT p.*, u.nom AS creatorName
                FROM post p
                INNER JOIN utilisateur u ON u.id = p.idCreateur
                ORDER BY p.creationDate DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function listPostsByCreator(int $idCreateur)
    {
        $sql = "SELECT p.*, u.nom AS creatorName
                FROM post p
                INNER JOIN utilisateur u ON u.id = p.idCreateur
                WHERE p.idCreateur = :idCreateur
                ORDER BY p.creationDate DESC";
        $query = $this->db->prepare($sql);
        $query->execute(['idCreateur' => $idCreateur]);
        return $query->fetchAll();
    }

    public function searchPosts(string $keyword)
    {
        $sql = "SELECT p.*, u.nom AS creatorName
                FROM post p
                INNER JOIN utilisateur u ON u.id = p.idCreateur
                WHERE u.nom LIKE :keyword
                   OR p.subject LIKE :keyword
                ORDER BY p.creationDate DESC";
        $query = $this->db->prepare($sql);
        $query->execute([
            'keyword' => '%' . $keyword . '%'
        ]);
        return $query->fetchAll();
    }

    public function listTrendingPosts()
    {
        $sql = "SELECT p.*, u.nom AS creatorName
                FROM post p
                INNER JOIN utilisateur u ON u.id = p.idCreateur
                ORDER BY p.numberOfView DESC, p.creationDate DESC";
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

    
    private function deleteCommentsByPostRecursive(string $postId): void
    {
        $topLevelComments = $this->commentC->listCommentsByPost($postId);

        foreach ($topLevelComments as $comment) {
            if (!empty($comment['id'])) {
                $this->commentC->deleteCommentAdmin((string)$comment['id']);
            }
        }
    }

    public function deletePost(string $id, int $idCreateur)
    {
        try {
            $this->db->beginTransaction();

            $sqlCheck = "SELECT id FROM post WHERE id = :id AND idCreateur = :idCreateur";
            $queryCheck = $this->db->prepare($sqlCheck);
            $queryCheck->execute([
                'id' => $id,
                'idCreateur' => $idCreateur
            ]);

            $post = $queryCheck->fetch(PDO::FETCH_ASSOC);
            if (!$post) {
                $this->db->rollBack();
                return false;
            }

            $this->deleteCommentsByPostRecursive($id);

            $sqlDelete = "DELETE FROM post WHERE id = :id AND idCreateur = :idCreateur";
            $queryDelete = $this->db->prepare($sqlDelete);
            $success = $queryDelete->execute([
                'id' => $id,
                'idCreateur' => $idCreateur
            ]);

            if (!$success) {
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
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
        $sql = "SELECT COUNT(*) AS total
                FROM post
                WHERE id = :id AND idCreateur = :idCreateur";
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
                    COUNT(*) AS totalPosts,
                    COALESCE(SUM(numberOfView), 0) AS totalViews,
                    COALESCE(SUM(numberOfLike), 0) AS totalLikes,
                    COALESCE(SUM(numberOfDislike), 0) AS totalDislikes
                FROM post
                WHERE idCreateur = :idCreateur";
        $query = $this->db->prepare($sql);
        $query->execute(['idCreateur' => $idCreateur]);
        return $query->fetch();
    }

    public function getLikeCount(string $id): int
    {
        $sql = "SELECT numberOfLike FROM post WHERE id = :id";
        $query = $this->db->prepare($sql);
        $query->execute(['id' => $id]);
        $result = $query->fetch();
        return (int)($result['numberOfLike'] ?? 0);
    }

    public function getDislikeCount(string $id): int
    {
        $sql = "SELECT numberOfDislike FROM post WHERE id = :id";
        $query = $this->db->prepare($sql);
        $query->execute(['id' => $id]);
        $result = $query->fetch();
        return (int)($result['numberOfDislike'] ?? 0);
    }

    public function deletePostAdmin(string $id): bool
    {
        try {
            $this->db->beginTransaction();

            $sqlCheck = "SELECT id FROM post WHERE id = :id";
            $queryCheck = $this->db->prepare($sqlCheck);
            $queryCheck->execute(['id' => $id]);

            $post = $queryCheck->fetch(PDO::FETCH_ASSOC);
            if (!$post) {
                $this->db->rollBack();
                return false;
            }

            $this->deleteCommentsByPostRecursive($id);

            $sqlDelete = "DELETE FROM post WHERE id = :id";
            $queryDelete = $this->db->prepare($sqlDelete);
            $success = $queryDelete->execute(['id' => $id]);

            if (!$success) {
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function getAdminStats()
    {
        $sql = "SELECT
                    COUNT(*) AS totalPosts,
                    COALESCE(SUM(numberOfView), 0) AS totalViews,
                    COALESCE(SUM(numberOfLike), 0) AS totalLikes,
                    COALESCE(SUM(numberOfDislike), 0) AS totalDislikes
                FROM post";
        $query = $this->db->query($sql);
        return $query->fetch();
    }
}