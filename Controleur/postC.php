<?php

require_once __DIR__ . '/../Modele/post.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/commentC.php';
require_once __DIR__ . '/../config_ai.php';

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

    public function deletePost(string $id, int $idCreateur)
    {
        $sql = "DELETE FROM post WHERE id = :id AND idCreateur = :idCreateur";
        $query = $this->db->prepare($sql);

        return $query->execute([
            'id' => $id,
            'idCreateur' => $idCreateur
        ]);
    }

    public function deletePostAdmin(string $id): bool
    {
        $sql = "DELETE FROM post WHERE id = :id";
        $query = $this->db->prepare($sql);

        return $query->execute(['id' => $id]);
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

    private function getAiApiKey(): string
    {
        $key = defined('GEMINI_API_KEY') ? trim((string)GEMINI_API_KEY) : '';
        if ($key === '' || $key === 'PASTE_YOUR_GEMINI_API_KEY_HERE') {
            throw new RuntimeException('Gemini API key is missing. Open config_ai.php and paste your API key first.');
        }
        return $key;
    }

    private function getAiModel(): string
    {
        $model = defined('GEMINI_MODEL') ? trim((string)GEMINI_MODEL) : '';
        return $model !== '' ? $model : 'gemini-2.5-flash';
    }

    private function fileToInlineData(string $absolutePath): ?array
    {
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            return null;
        }

        $mime = mime_content_type($absolutePath) ?: '';
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($mime, $allowed, true)) {
            return null;
        }

        $data = file_get_contents($absolutePath);
        if ($data === false || $data === '') {
            return null;
        }

        return [
            'inline_data' => [
                'mime_type' => $mime,
                'data' => base64_encode($data)
            ]
        ];
    }

    private function getInlineImagePartFromExistingPath(?string $relativePath): ?array
    {
        $relativePath = trim((string)$relativePath);
        if ($relativePath === '') {
            return null;
        }

        $publicRoot = realpath(__DIR__ . '/../Vue/public');
        if ($publicRoot === false) {
            return null;
        }

        $absolutePath = $publicRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
        return $this->fileToInlineData($absolutePath);
    }

    private function getInlineImagePartFromUploadedFile(string $fieldName = 'image'): ?array
    {
        if (empty($_FILES[$fieldName]) || (($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE)) {
            return null;
        }

        if (($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmpPath = $_FILES[$fieldName]['tmp_name'] ?? '';
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            return null;
        }

        return $this->fileToInlineData($tmpPath);
    }

    private function parseGeneratedText(array $responseData): string
    {
        if (!empty($responseData['candidates'][0]['content']['parts'])) {
            $parts = $responseData['candidates'][0]['content']['parts'];
            $texts = [];
            foreach ($parts as $part) {
                if (!empty($part['text'])) {
                    $texts[] = $part['text'];
                }
            }
            return trim(implode("\n", $texts));
        }
        return '';
    }

    public function generatePostContentWithAi(
        string $brief,
        string $style = '',
        int $sentenceCount = 4,
        string $currentContent = '',
        string $mode = 'generate',
        ?string $existingImagePath = null
    ): string {
        $brief = trim($brief);
        $style = trim($style);
        $currentContent = trim($currentContent);
        $mode = strtolower(trim($mode));
        $sentenceCount = max(1, min(12, $sentenceCount));

        if ($brief === '') {
            throw new InvalidArgumentException('Brief is required.');
        }

        $instruction = $mode === 'enhance'
            ? "You are a social media writing assistant for a university project. Rewrite and enhance the user's post content. Keep the meaning clear, natural, and ready to publish. Return only the final post text with no title, no bullet points, no labels, no quotation marks, and no explanations. Target exactly {$sentenceCount} sentence(s)."
            : "You are a social media writing assistant for a university project. Generate a ready-to-publish post from the user's brief. Return only the final post text with no title, no bullet points, no labels, no quotation marks, and no explanations. Target exactly {$sentenceCount} sentence(s).";

        $styleText = $style !== '' ? "Writing style requested by the user: {$style}." : "Writing style requested by the user: clear, engaging, natural social-media style.";
        $briefText = "User brief: {$brief}.";
        $currentContentText = ($mode === 'enhance' && $currentContent !== '') ? "Current content to improve: {$currentContent}." : '';
        $imageText = 'If an image is provided, use it only as extra context. Keep the output focused on the user brief.';

        $parts = [
            ['text' => $instruction],
            ['text' => $styleText],
            ['text' => $briefText],
        ];

        if ($currentContentText !== '') {
            $parts[] = ['text' => $currentContentText];
        }

        $parts[] = ['text' => $imageText];

        $inlineImage = $this->getInlineImagePartFromUploadedFile('image');
        if ($inlineImage === null && $existingImagePath) {
            $inlineImage = $this->getInlineImagePartFromExistingPath($existingImagePath);
        }
        if ($inlineImage !== null) {
            $parts[] = $inlineImage;
        }

        $payload = [
            'contents' => [[
                'role' => 'user',
                'parts' => $parts
            ]],
            'generationConfig' => [
                'temperature' => 0.8,
                'topP' => 0.95,
                'maxOutputTokens' => 500
            ]
        ];

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($this->getAiModel()) . ':generateContent';

        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL is not enabled in PHP. Enable cURL in XAMPP before using the AI feature.');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $this->getAiApiKey(),
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => 60,
        ]);

        $raw = curl_exec($ch);
        $curlError = curl_error($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('Unable to contact Gemini API: ' . $curlError);
        }

        $decoded = json_decode($raw, true);
        if ($statusCode >= 400) {
            $message = $decoded['error']['message'] ?? ('Gemini API returned HTTP ' . $statusCode . '.');
            throw new RuntimeException($message);
        }

        $content = $this->parseGeneratedText(is_array($decoded) ? $decoded : []);
        if ($content === '') {
            throw new RuntimeException('The AI returned an empty response. Try changing the brief or style.');
        }

        return $content;
    }
}
