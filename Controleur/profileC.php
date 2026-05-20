<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Modele/profile.php';

class ProfileC
{
    private PDO $db;
    private const MAX_UPLOAD_SIZE = 2097152;
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private ?bool $profileHasAboutMe = null;
    private ?bool $userHasFaceDescriptor = null;

    public function __construct()
    {
        $this->db = config::getConnexion();
    }

    public function profileSupportsAboutMe(): bool
    {
        if ($this->profileHasAboutMe !== null) {
            return $this->profileHasAboutMe;
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM profile LIKE 'aboutMe'");
            $this->profileHasAboutMe = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $this->profileHasAboutMe = false;
        }

        return $this->profileHasAboutMe;
    }

    public function userSupportsFaceDescriptor(): bool
    {
        if ($this->userHasFaceDescriptor !== null) {
            return $this->userHasFaceDescriptor;
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM utilisateur LIKE 'face_descriptor'");
            $this->userHasFaceDescriptor = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $this->userHasFaceDescriptor = false;
        }

        return $this->userHasFaceDescriptor;
    }

    public function getProfileByUserId(int $userId)
    {
        $aboutSelect = $this->profileSupportsAboutMe() ? 'aboutMe' : 'NULL AS aboutMe';
        $stmt = $this->db->prepare("SELECT idProfile, idUtilisateur, imageName, $aboutSelect, createdAt, updatedAt FROM profile WHERE idUtilisateur = ? LIMIT 1");
        $stmt->execute([$userId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getProfileImageName(int $userId): ?string
    {
        $profile = $this->getProfileByUserId($userId);
        $imageName = trim((string)($profile['imageName'] ?? ''));

        return $imageName !== '' ? $imageName : null;
    }

    public function getProfileAboutMe(int $userId): string
    {
        $profile = $this->getProfileByUserId($userId);
        return trim((string)($profile['aboutMe'] ?? ''));
    }

    public function upsertProfileImage(int $userId, ?string $imageName): bool
    {
        return $this->upsertProfileDetails($userId, $imageName, null, true, false);
    }

    public function upsertProfileDetails(
        int $userId,
        ?string $imageName,
        ?string $aboutMe,
        bool $updateImage = false,
        bool $updateAbout = true
    ): bool {
        $existing = $this->getProfileByUserId($userId);
        $hasAboutMe = $this->profileSupportsAboutMe();

        if ($existing) {
            $sets = ['updatedAt = NOW()'];
            $params = ['idUtilisateur' => $userId];

            if ($updateImage) {
                $sets[] = 'imageName = :imageName';
                $params['imageName'] = $imageName;
            }

            if ($updateAbout && $hasAboutMe) {
                $sets[] = 'aboutMe = :aboutMe';
                $params['aboutMe'] = $aboutMe;
            }

            $sql = 'UPDATE profile SET ' . implode(', ', $sets) . ' WHERE idUtilisateur = :idUtilisateur';
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        }

        if ($hasAboutMe) {
            $stmt = $this->db->prepare('
                INSERT INTO profile (idUtilisateur, imageName, aboutMe, createdAt, updatedAt)
                VALUES (:idUtilisateur, :imageName, :aboutMe, NOW(), NOW())
            ');

            return $stmt->execute([
                'idUtilisateur' => $userId,
                'imageName' => $updateImage ? $imageName : null,
                'aboutMe' => $updateAbout ? $aboutMe : null,
            ]);
        }

        $stmt = $this->db->prepare('
            INSERT INTO profile (idUtilisateur, imageName, createdAt, updatedAt)
            VALUES (:idUtilisateur, :imageName, NOW(), NOW())
        ');

        return $stmt->execute([
            'idUtilisateur' => $userId,
            'imageName' => $updateImage ? $imageName : null,
        ]);
    }

    public function updateUserDisplayName(int $userId, string $name): bool
    {
        $stmt = $this->db->prepare('UPDATE utilisateur SET nom = :nom WHERE id = :id');
        return $stmt->execute([
            'nom' => $name,
            'id' => $userId,
        ]);
    }

    public function updateUserFaceDescriptor(int $userId, ?string $faceDescriptor): bool
    {
        if (!$this->userSupportsFaceDescriptor()) {
            return false;
        }

        $stmt = $this->db->prepare('UPDATE utilisateur SET face_descriptor = :face_descriptor WHERE id = :id');
        return $stmt->execute([
            'face_descriptor' => $faceDescriptor,
            'id' => $userId,
        ]);
    }

    public function getUserById(int $userId)
    {
        $faceSelect = $this->userSupportsFaceDescriptor() ? 'face_descriptor' : 'NULL AS face_descriptor';
        $stmt = $this->db->prepare("SELECT id, nom, email, role, $faceSelect FROM utilisateur WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getProfileImageUrl(int $userId, string $relativeContext): ?string
    {
        $imageName = $this->getProfileImageName($userId);
        if ($imageName === null || !$this->profileImageExists($imageName)) {
            return null;
        }

        return rtrim($relativeContext, '/') . '/' . rawurlencode($imageName);
    }

    public function storeUploadedImage(int $userId, array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['success' => true, 'imageName' => null];
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'The image upload failed. Please try again.'];
        }

        if ((int)($file['size'] ?? 0) > self::MAX_UPLOAD_SIZE) {
            return ['success' => false, 'message' => 'Profile photo must be 2MB or smaller.'];
        }

        $tmpPath = (string)($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            return ['success' => false, 'message' => 'Invalid uploaded file.'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $tmpPath) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        if (!is_string($mimeType) || !array_key_exists($mimeType, self::MIME_EXTENSIONS)) {
            return ['success' => false, 'message' => 'Only JPG, PNG, and WEBP images are allowed.'];
        }

        $uploadDir = self::getUploadDirectory();
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            return ['success' => false, 'message' => 'Profile upload folder is not available.'];
        }

        $extension = self::MIME_EXTENSIONS[$mimeType];
        $imageName = sprintf(
            'profile_%d_%s_%s.%s',
            $userId,
            date('Ymd_His'),
            bin2hex(random_bytes(4)),
            $extension
        );
        $destination = $uploadDir . DIRECTORY_SEPARATOR . $imageName;

        if (!move_uploaded_file($tmpPath, $destination)) {
            return ['success' => false, 'message' => 'Could not save the uploaded image.'];
        }

        return ['success' => true, 'imageName' => $imageName];
    }

    public function profileImageExists(string $imageName): bool
    {
        $path = $this->getSafeProfileImagePath($imageName);
        return $path !== null && is_file($path);
    }

    public function deleteProfileImage(string $imageName): void
    {
        $path = $this->getSafeProfileImagePath($imageName);
        if ($path !== null && is_file($path)) {
            @unlink($path);
        }
    }

    public function getSafeProfileImagePath(string $imageName): ?string
    {
        $imageName = trim($imageName);
        if ($imageName !== basename($imageName)) {
            return null;
        }

        $imageName = basename($imageName);
        if ($imageName === '' || !preg_match('/^profile_[a-z0-9][a-z0-9_-]{0,120}\.(jpg|jpeg|png|webp)$/i', $imageName)) {
            return null;
        }

        $uploadDir = self::getUploadDirectory();
        $uploadRoot = realpath($uploadDir);
        if ($uploadRoot === false) {
            return null;
        }

        $path = $uploadRoot . DIRECTORY_SEPARATOR . $imageName;
        $directory = realpath(dirname($path));

        return $directory === $uploadRoot ? $path : null;
    }

    public static function getUploadDirectory(): string
    {
        return __DIR__ . '/../Vue/public/uploads/profile';
    }
}

?>
