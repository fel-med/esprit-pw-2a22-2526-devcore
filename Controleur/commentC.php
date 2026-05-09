<?php

require_once __DIR__ . '/../Modele/comment.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../config_stt.php';

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

    public function buildLinkFromLegacyInput(?string $commentedItem, ?string $idCommentedElement): array
    {
        $commentedItem = strtolower(trim((string)$commentedItem));
        $idCommentedElement = trim((string)$idCommentedElement);

        if ($commentedItem === 'comment') {
            return [
                'idPost' => null,
                'idComment' => $idCommentedElement !== '' ? $idCommentedElement : null
            ];
        }

        return [
            'idPost' => $idCommentedElement !== '' ? $idCommentedElement : null,
            'idComment' => null
        ];
    }

    private function getLegacyTypeFromRow(array $row): string
    {
        return !empty($row['idComment']) ? 'comment' : 'post';
    }

    private function getLegacyElementIdFromRow(array $row): ?string
    {
        return !empty($row['idComment']) ? $row['idComment'] : ($row['idPost'] ?? null);
    }

    private function hydrateLegacyShape(array $row): array
    {
        $row['commentedItem'] = $this->getLegacyTypeFromRow($row);
        $row['idCommentedElement'] = $this->getLegacyElementIdFromRow($row);
        return $row;
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
                    idPost,
                    idComment,
                    idUser,
                    `text`,
                    Sticker,
                    image,
                    numberOfLike,
                    numberOfDislike
                ) VALUES (
                    :id,
                    :idPost,
                    :idComment,
                    :idUser,
                    :text,
                    :sticker,
                    :image,
                    :numberOfLike,
                    :numberOfDislike
                )";

        $query = $this->db->prepare($sql);
        return $query->execute([
            'id' => $comment->getId(),
            'idPost' => $comment->getIdPost(),
            'idComment' => $comment->getIdComment(),
            'idUser' => $comment->getIdUser(),
            'text' => $comment->getText(),
            'sticker' => $comment->getSticker(),
            'image' => $comment->getImage(),
            'numberOfLike' => $comment->getNumberOfLike(),
            'numberOfDislike' => $comment->getNumberOfDislike(),
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
            'id' => $comment->getId(),
            'idUser' => $comment->getIdUser(),
            'text' => $comment->getText(),
            'sticker' => $comment->getSticker(),
            'image' => $comment->getImage(),
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

        $row = $query->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }

        return $this->hydrateLegacyShape($row);
    }

    public function listCommentsByPost(string $postId): array
    {
        $sql = "SELECT c.*, u.nom AS userName
                FROM `comment` c
                LEFT JOIN utilisateur u ON u.id = c.idUser
                WHERE c.idPost = :postId
                ORDER BY c.id ASC";

        $query = $this->db->prepare($sql);
        $query->execute(['postId' => $postId]);

        $rows = $query->fetchAll(PDO::FETCH_ASSOC);
        return array_map([$this, 'hydrateLegacyShape'], $rows);
    }

    public function listRepliesByComment(string $commentId): array
    {
        $sql = "SELECT c.*, u.nom AS userName
                FROM `comment` c
                LEFT JOIN utilisateur u ON u.id = c.idUser
                WHERE c.idComment = :commentId
                ORDER BY c.id ASC";

        $query = $this->db->prepare($sql);
        $query->execute(['commentId' => $commentId]);

        $rows = $query->fetchAll(PDO::FETCH_ASSOC);
        return array_map([$this, 'hydrateLegacyShape'], $rows);
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
                LEFT JOIN post p ON p.id = c.idPost
                ORDER BY c.id DESC";

        $rows = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return array_map([$this, 'hydrateLegacyShape'], $rows);
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
                usort($flat, static function ($a, $b) {
                    return strcmp((string)$b['id'], (string)$a['id']);
                });
                return $flat;

            case 'commentId':
                return $this->buildTree($this->listRepliesByComment($keyword));

            case 'creator':
                $sql = "SELECT c.*, u.nom AS userName, p.subject AS postSubject
                        FROM `comment` c
                        LEFT JOIN utilisateur u ON u.id = c.idUser
                        LEFT JOIN post p ON p.id = c.idPost
                        WHERE u.nom LIKE :keyword OR CAST(c.idUser AS CHAR) LIKE :keyword
                        ORDER BY c.id DESC";
                $query = $this->db->prepare($sql);
                $query->execute(['keyword' => '%' . $keyword . '%']);
                $rows = $query->fetchAll(PDO::FETCH_ASSOC);
                return array_map([$this, 'hydrateLegacyShape'], $rows);

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


    public function transcribeAudioWithGroq(string $filePath, string $originalName = 'comment.webm', string $language = 'en'): array
    {
        if (!defined('STT_API_KEY') || trim((string) STT_API_KEY) === '' || (string) STT_API_KEY === 'PASTE_YOUR_GROQ_API_KEY_HERE') {
            return ['success' => false, 'message' => 'Set your Groq API key in config_stt.php first.'];
        }
        if (!function_exists('curl_init')) {
            return ['success' => false, 'message' => 'cURL is not enabled in PHP. Enable php_curl in XAMPP.'];
        }
        if (!is_file($filePath) || filesize($filePath) <= 0) {
            return ['success' => false, 'message' => 'Audio file is missing or empty.'];
        }

        $language = strtolower(trim($language));
        if ($language === '') {
            $language = 'en';
        }
        if (strlen($language) > 2) {
            $language = substr($language, 0, 2);
        }

        $mimeType = mime_content_type($filePath) ?: 'audio/webm';
        $endpoint = rtrim((string) STT_API_BASE_URL, '/') . '/audio/transcriptions';
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . STT_API_KEY,
            ],
            CURLOPT_POSTFIELDS => [
                'model' => defined('STT_MODEL') ? (string) STT_MODEL : 'whisper-large-v3-turbo',
                'language' => $language,
                'response_format' => 'json',
                'temperature' => '0',
                'file' => new CURLFile($filePath, $mimeType, $originalName),
            ],
            CURLOPT_TIMEOUT => 90,
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            return ['success' => false, 'message' => 'Speech API request failed: ' . $curlError];
        }

        $decoded = json_decode($response, true);
        if ($statusCode < 200 || $statusCode >= 300) {
            return ['success' => false, 'message' => $decoded['error']['message'] ?? ('Speech API HTTP error ' . $statusCode)];
        }

        $text = trim((string) ($decoded['text'] ?? ''));
        if ($text === '') {
            return ['success' => false, 'message' => 'No transcription text was returned.'];
        }

        return ['success' => true, 'text' => $text];
    }

}