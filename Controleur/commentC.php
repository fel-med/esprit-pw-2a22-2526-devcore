<?php

require_once __DIR__ . '/../Modele/comment.php';
require_once __DIR__ . '/../config.php';

class CommentC
{
    private PDO $db;

    public function __construct()
    {
        $this->db = config::getConnexion();
    }

    private function generateUuid(): string
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

    private function normalizeElementType(string $type): string
    {
        $type = strtolower(trim($type));
        return $type === SocialElementType::COMMENT ? SocialElementType::COMMENT : SocialElementType::POST;
    }

    public function handleCommentImage(string $fieldName = 'image'): ?string
    {
        if (empty($_FILES[$fieldName]) || (($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE)) {
            return null;
        }

        if (($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmpPath = $_FILES[$fieldName]['tmp_name'];
        if (!is_uploaded_file($tmpPath)) {
            return null;
        }

        $mimeType = mime_content_type($tmpPath) ?: '';
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];

        if (!isset($allowed[$mimeType])) {
            return null;
        }

        $fileSize = (int)($_FILES[$fieldName]['size'] ?? 0);
        if ($fileSize > 5 * 1024 * 1024) {
            return null;
        }

        $publicRoot = realpath(__DIR__ . '/../Vue/public');
        if ($publicRoot === false) {
            return null;
        }

        $targetDir = $publicRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'comments';
        if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
            return null;
        }

        $originalName = $_FILES[$fieldName]['name'] ?? 'comment-image';
        $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '-', pathinfo($originalName, PATHINFO_FILENAME));
        $baseName = trim((string)$baseName, '-');
        if ($baseName === '') {
            $baseName = 'comment';
        }

        $filename = $baseName . '-' . uniqid('', true) . '.' . $allowed[$mimeType];
        $absolutePath = $targetDir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($tmpPath, $absolutePath)) {
            return null;
        }

        return 'uploads/comments/' . $filename;
    }

    public function addComment(Comment $comment): bool
    {
        $id = $comment->getId();
        if (empty($id)) {
            $id = $this->generateUuid();
            $comment->setId($id);
        }

        $sql = "INSERT INTO `comment` (
                    id,
                    idCommentedElement,
                    idUser,
                    commentedItem,
                    `text`,
                    Sticker,
                    image,
                    numberOfLike,
                    numberOfDislike
                ) VALUES (
                    :id,
                    :idCommentedElement,
                    :idUser,
                    :commentedItem,
                    :text,
                    :sticker,
                    :image,
                    :numberOfLike,
                    :numberOfDislike
                )";

        $query = $this->db->prepare($sql);
        return $query->execute([
            'id'                 => $comment->getId(),
            'idCommentedElement' => $comment->getIdCommentedElement(),
            'idUser'             => $comment->getIdUser(),
            'commentedItem'      => $this->normalizeElementType($comment->getCommentedItem()),
            'text'               => $comment->getText(),
            'sticker'            => $comment->getSticker(),
            'image'              => $comment->getImage(),
            'numberOfLike'       => $comment->getNumberOfLike(),
            'numberOfDislike'    => $comment->getNumberOfDislike(),
        ]);
    }

    public function updateComment(Comment $comment): bool
    {
        $sql = "UPDATE `comment`
                SET `text` = :text,
                    Sticker = :sticker,
                    image = :image
                WHERE id = :id AND idUser = :idUser";

        $query = $this->db->prepare($sql);
        return $query->execute([
            'id'      => $comment->getId(),
            'idUser'  => $comment->getIdUser(),
            'text'    => $comment->getText(),
            'sticker' => $comment->getSticker(),
            'image'   => $comment->getImage(),
        ]);
    }

    public function showComment(string $id)
    {
        $sql = "SELECT c.*, u.nom AS userName
                FROM `comment` c
                LEFT JOIN utilisateur u ON u.id = c.idUser
                WHERE c.id = :id";

        $query = $this->db->prepare($sql);
        $query->execute(['id' => $id]);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function listCommentsByElement(string $type, string $elementId): array
    {
        $sql = "SELECT c.*, u.nom AS userName
                FROM `comment` c
                LEFT JOIN utilisateur u ON u.id = c.idUser
                WHERE c.commentedItem = :commentedItem
                  AND c.idCommentedElement = :idCommentedElement
                ORDER BY c.id ASC";

        $query = $this->db->prepare($sql);
        $query->execute([
            'commentedItem'      => $this->normalizeElementType($type),
            'idCommentedElement' => $elementId,
        ]);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listCommentsByPost(string $postId): array
    {
        return $this->listCommentsByElement(SocialElementType::POST, $postId);
    }

    public function listRepliesByComment(string $commentId): array
    {
        return $this->listCommentsByElement(SocialElementType::COMMENT, $commentId);
    }

    private function buildTree(array $comments): array
    {
        foreach ($comments as &$comment) {
            $comment['replies'] = $this->buildTree($this->listRepliesByComment($comment['id']));
        }
        unset($comment);
        return $comments;
    }

    public function getCommentsTreeByPost(string $postId): array
    {
        return $this->buildTree($this->listCommentsByPost($postId));
    }

    private function flattenTree(array $tree, array &$flat): void
    {
        foreach ($tree as $item) {
            $copy = $item;
            unset($copy['replies']);
            $flat[] = $copy;
            if (!empty($item['replies'])) {
                $this->flattenTree($item['replies'], $flat);
            }
        }
    }

    public function listLatestCommentsByPost(string $postId, int $limit = 2): array
    {
        $tree = $this->getCommentsTreeByPost($postId);
        $flat = [];
        $this->flattenTree($tree, $flat);

        usort($flat, static function ($a, $b) {
            return strcmp((string)$b['id'], (string)$a['id']);
        });

        return array_slice($flat, 0, max(0, $limit));
    }

    public function countCommentsByPost(string $postId): int
    {
        $tree = $this->getCommentsTreeByPost($postId);
        $flat = [];
        $this->flattenTree($tree, $flat);
        return count($flat);
    }

    public function incrementLike(string $id): bool
    {
        $query = $this->db->prepare("UPDATE `comment` SET numberOfLike = numberOfLike + 1 WHERE id = :id");
        return $query->execute(['id' => $id]);
    }

    public function incrementDislike(string $id): bool
    {
        $query = $this->db->prepare("UPDATE `comment` SET numberOfDislike = numberOfDislike + 1 WHERE id = :id");
        return $query->execute(['id' => $id]);
    }

    public function getLikeCount(string $id): int
    {
        $query = $this->db->prepare("SELECT numberOfLike FROM `comment` WHERE id = :id");
        $query->execute(['id' => $id]);
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return (int)($result['numberOfLike'] ?? 0);
    }

    public function getDislikeCount(string $id): int
    {
        $query = $this->db->prepare("SELECT numberOfDislike FROM `comment` WHERE id = :id");
        $query->execute(['id' => $id]);
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return (int)($result['numberOfDislike'] ?? 0);
    }

    public function userOwnsComment(string $commentId, int $idUser): bool
    {
        $query = $this->db->prepare("SELECT COUNT(*) AS total FROM `comment` WHERE id = :id AND idUser = :idUser");
        $query->execute([
            'id' => $commentId,
            'idUser' => $idUser
        ]);
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0) > 0;
    }

    public function removeStoredImage(?string $relativePath): void
    {
        if (empty($relativePath)) {
            return;
        }

        $publicRoot = realpath(__DIR__ . '/../Vue/public');
        if ($publicRoot === false) {
            return;
        }

        $absolute = $publicRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }

    private function deleteCommentRecursiveInternal(string $commentId): bool
    {
        $comment = $this->showComment($commentId);
        if (!$comment) {
            return false;
        }

        $replies = $this->listRepliesByComment($commentId);
        foreach ($replies as $reply) {
            if (!$this->deleteCommentRecursiveInternal((string)$reply['id'])) {
                return false;
            }
        }

        $query = $this->db->prepare("DELETE FROM `comment` WHERE id = :id");
        $success = $query->execute(['id' => $commentId]);

        if ($success) {
            $this->removeStoredImage($comment['image'] ?? null);
        }

        return $success;
    }

    public function deleteComment(string $commentId, ?int $idUser = null): bool
    {
        $comment = $this->showComment($commentId);
        if (!$comment) {
            return false;
        }

        if ($idUser !== null && (int)($comment['idUser'] ?? 0) !== $idUser) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $success = $this->deleteCommentRecursiveInternal($commentId);

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

    public function deleteCommentAdmin(string $commentId): bool
    {
        return $this->deleteComment($commentId, null);
    }

    public function listAllCommentsAdmin(): array
    {
        $sql = "SELECT c.*, u.nom AS userName, p.subject AS postSubject
                FROM `comment` c
                LEFT JOIN utilisateur u ON u.id = c.idUser
                LEFT JOIN post p ON c.commentedItem = 'post' AND p.id = c.idCommentedElement
                ORDER BY c.id DESC";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchCommentsAdmin(string $searchType, string $keyword): array
    {
        $searchType = trim($searchType);
        $keyword = trim($keyword);

        if ($keyword === '') {
            return $this->listAllCommentsAdmin();
        }

        switch ($searchType) {
            case 'postId':
                $tree = $this->getCommentsTreeByPost($keyword);
                $flat = [];
                $this->flattenTree($tree, $flat);
                return $flat;

            case 'commentId':
                return $this->buildTree($this->listRepliesByComment($keyword));

            case 'creator':
                $sql = "SELECT c.*, u.nom AS userName, p.subject AS postSubject
                        FROM `comment` c
                        LEFT JOIN utilisateur u ON u.id = c.idUser
                        LEFT JOIN post p ON c.commentedItem = 'post' AND p.id = c.idCommentedElement
                        WHERE u.nom LIKE :keyword OR CAST(c.idUser AS CHAR) LIKE :keyword
                        ORDER BY c.id DESC";
                $query = $this->db->prepare($sql);
                $query->execute(['keyword' => '%' . $keyword . '%']);
                return $query->fetchAll(PDO::FETCH_ASSOC);

            default:
                return $this->listAllCommentsAdmin();
        }
    }

    public function getAdminStats(): array
    {
        $query = $this->db->query("
            SELECT
                COUNT(*) AS totalComments,
                COALESCE(SUM(numberOfLike), 0) AS totalLikes,
                COALESCE(SUM(numberOfDislike), 0) AS totalDislikes
            FROM `comment`
        ");

        return $query->fetch(PDO::FETCH_ASSOC) ?: [
            'totalComments' => 0,
            'totalLikes' => 0,
            'totalDislikes' => 0
        ];
    }
}