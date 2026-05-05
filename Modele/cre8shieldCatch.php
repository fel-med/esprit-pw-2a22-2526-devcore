<?php

require_once __DIR__ . '/../config.php';

/**
 * Cre8ShieldCatch — DAO for the cre8shield_catches table.
 *
 * The table is created and managed outside of this class. It is expected to expose:
 *   id_catch INT PK AUTO_INCREMENT,
 *   risk_level VARCHAR(16),                 -- 'low' | 'medium' | 'high'
 *   risk_score INT,
 *   risk_categories TEXT,                   -- JSON or comma-separated text
 *   finding_summary TEXT,
 *   safe_recommendations TEXT,
 *   raw_message_snapshot TEXT,
 *   sanitized_message TEXT,
 *   source_type VARCHAR(40),                -- 'chat_message' | 'chat_link' | 'page_scan' | ...
 *   source_id VARCHAR(64) NULL,
 *   source_label VARCHAR(255) NULL,
 *   page VARCHAR(80) NULL,
 *   mode VARCHAR(80) NULL,
 *   role VARCHAR(40) NULL,
 *   reporter_user_id INT NULL,
 *   reporter_role VARCHAR(40) NULL,
 *   reported_user_id INT NULL,
 *   reported_role VARCHAR(40) NULL,
 *   ai_decision VARCHAR(32) NULL,
 *   ai_rationale TEXT NULL,
 *   catch_hash CHAR(64) NULL,
 *   status VARCHAR(32) DEFAULT 'open',      -- 'open' | 'reviewed' | 'ignored' | 'escalated' | 'resolved'
 *   admin_notes TEXT NULL,
 *   reviewed_by INT NULL,
 *   reviewed_at DATETIME NULL,
 *   created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
 *   updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 *
 * No foreign keys are added; reporter/reported user ids are stored as plain integers.
 * This class never alters existing tables and never auto-punishes users.
 */
class Cre8ShieldCatch
{
    private const ALLOWED_RISK_LEVELS = ['low', 'medium', 'high'];
    private const ALLOWED_STATUSES = ['open', 'reviewed', 'ignored', 'escalated', 'resolved'];
    private const PERSIST_RISK_LEVELS = ['medium', 'high'];

    private $pdo;
    private ?bool $tableAvailable = null;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?: config::getConnexion();
    }

    /**
     * Insert a new catch unless the same logical event already exists.
     *
     * Returns: ['stored' => bool, 'id' => ?int, 'duplicate' => bool, 'reason' => string].
     * Reasons: 'inserted', 'duplicate', 'low_risk_skipped', 'invalid', 'no_table', 'db_error'.
     */
    public function createCatchIfNotDuplicate(array $data): array
    {
        if (!$this->isAvailable()) {
            return ['stored' => false, 'id' => null, 'duplicate' => false, 'reason' => 'no_table'];
        }

        $riskLevel = $this->normalizeRiskLevel((string) ($data['risk_level'] ?? ''));
        if ($riskLevel === '' || !in_array($riskLevel, self::PERSIST_RISK_LEVELS, true)) {
            return ['stored' => false, 'id' => null, 'duplicate' => false, 'reason' => 'low_risk_skipped'];
        }

        $row = $this->normalizeRowForInsert($data, $riskLevel);
        $row['catch_hash'] = $this->computeCatchHash($row);

        $existingId = $this->findIdByHash($row['catch_hash']);
        if ($existingId !== null) {
            return ['stored' => false, 'id' => $existingId, 'duplicate' => true, 'reason' => 'duplicate'];
        }

        try {
            $sql = 'INSERT INTO cre8shield_catches (
                        risk_level, risk_score, risk_categories, finding_summary, safe_recommendations,
                        raw_message_snapshot, sanitized_message,
                        source_type, source_id, source_label, page, mode, role,
                        reporter_user_id, reporter_role, reported_user_id, reported_role,
                        ai_decision, ai_rationale, catch_hash, status, admin_notes,
                        reviewed_by, reviewed_at, created_at, updated_at
                    ) VALUES (
                        :risk_level, :risk_score, :risk_categories, :finding_summary, :safe_recommendations,
                        :raw_message_snapshot, :sanitized_message,
                        :source_type, :source_id, :source_label, :page, :mode, :role,
                        :reporter_user_id, :reporter_role, :reported_user_id, :reported_role,
                        :ai_decision, :ai_rationale, :catch_hash, :status, :admin_notes,
                        NULL, NULL, NOW(), NOW()
                    )';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':risk_level' => $row['risk_level'],
                ':risk_score' => $row['risk_score'],
                ':risk_categories' => $row['risk_categories'],
                ':finding_summary' => $row['finding_summary'],
                ':safe_recommendations' => $row['safe_recommendations'],
                ':raw_message_snapshot' => $row['raw_message_snapshot'],
                ':sanitized_message' => $row['sanitized_message'],
                ':source_type' => $row['source_type'],
                ':source_id' => $row['source_id'],
                ':source_label' => $row['source_label'],
                ':page' => $row['page'],
                ':mode' => $row['mode'],
                ':role' => $row['role'],
                ':reporter_user_id' => $row['reporter_user_id'],
                ':reporter_role' => $row['reporter_role'],
                ':reported_user_id' => $row['reported_user_id'],
                ':reported_role' => $row['reported_role'],
                ':ai_decision' => $row['ai_decision'],
                ':ai_rationale' => $row['ai_rationale'],
                ':catch_hash' => $row['catch_hash'],
                ':status' => $row['status'] ?: 'open',
                ':admin_notes' => $row['admin_notes'],
            ]);

            $newId = (int) $this->pdo->lastInsertId();

            return ['stored' => true, 'id' => $newId, 'duplicate' => false, 'reason' => 'inserted'];
        } catch (Throwable $e) {
            return ['stored' => false, 'id' => null, 'duplicate' => false, 'reason' => 'db_error'];
        }
    }

    public function listByRisk(string $riskLevel, int $limit = 100, int $offset = 0): array
    {
        $level = $this->normalizeRiskLevel($riskLevel);
        if ($level === '' || !$this->isAvailable()) {
            return [];
        }
        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);

        try {
            // Open + escalated catches stay on this list. Escalated rows float to
            // the top so admins see priority items first; everything else then
            // sorts by created_at DESC.
            $sql = "SELECT * FROM cre8shield_catches
                    WHERE risk_level = :rl AND status NOT IN ('reviewed','resolved','ignored')
                    ORDER BY (status = 'escalated') DESC, created_at DESC, id_catch DESC
                    LIMIT $limit OFFSET $offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':rl' => $level]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    public function listByStatus(string $status, int $limit = 100, int $offset = 0): array
    {
        $status = $this->normalizeStatus($status);
        if (!$this->isAvailable()) {
            return [];
        }
        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);

        try {
            $sql = "SELECT * FROM cre8shield_catches
                    WHERE status = :st
                    ORDER BY COALESCE(reviewed_at, updated_at, created_at) DESC, id_catch DESC
                    LIMIT $limit OFFSET $offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':st' => $status]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    public function listReviewed(int $limit = 100, int $offset = 0): array
    {
        if (!$this->isAvailable()) {
            return [];
        }
        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);

        try {
            // Reviewed/resolved/ignored only — escalated has its own tab so it does
            // not get buried in the "Reviewed" archive view.
            $sql = "SELECT * FROM cre8shield_catches
                    WHERE status IN ('reviewed','resolved','ignored')
                    ORDER BY COALESCE(reviewed_at, updated_at, created_at) DESC, id_catch DESC
                    LIMIT $limit OFFSET $offset";
            $stmt = $this->pdo->query($sql);

            return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getCatchById(int $id): ?array
    {
        if ($id <= 0 || !$this->isAvailable()) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare('SELECT * FROM cre8shield_catches WHERE id_catch = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function countByStatuses(array $statuses): int
    {
        if (!$this->isAvailable() || $statuses === []) {
            return 0;
        }
        $placeholders = [];
        $params = [];
        foreach (array_values($statuses) as $i => $st) {
            $key = ':st' . $i;
            $placeholders[] = $key;
            $params[$key] = (string) $st;
        }
        try {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) AS c FROM cre8shield_catches WHERE status IN (' . implode(',', $placeholders) . ')');
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) ($row['c'] ?? 0);
        } catch (Throwable $e) {
            return 0;
        }
    }

    public function countByRiskAndOpen(string $riskLevel): int
    {
        $level = $this->normalizeRiskLevel($riskLevel);
        if ($level === '' || !$this->isAvailable()) {
            return 0;
        }
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) AS c FROM cre8shield_catches
                                         WHERE risk_level = :rl AND status NOT IN ('reviewed','resolved','ignored')");
            $stmt->execute([':rl' => $level]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) ($row['c'] ?? 0);
        } catch (Throwable $e) {
            return 0;
        }
    }

    public function markReviewed(int $id, int $adminId): bool
    {
        return $this->updateStatus($id, $adminId, 'reviewed');
    }

    public function ignoreCatch(int $id, int $adminId): bool
    {
        return $this->updateStatus($id, $adminId, 'ignored');
    }

    public function escalateCatch(int $id, int $adminId): bool
    {
        $ok = $this->updateStatus($id, $adminId, 'escalated');
        if ($ok) {
            $this->ensureDefaultAdminNote($id, 'Escalated for priority review');
        }

        return $ok;
    }

    /**
     * Set admin_notes only when it is currently NULL or blank. Used to attach a
     * meaningful default reason when an admin escalates a catch without typing one.
     * Never overwrites an existing note.
     */
    private function ensureDefaultAdminNote(int $id, string $defaultNote): void
    {
        if ($id <= 0 || !$this->isAvailable()) {
            return;
        }
        try {
            $stmt = $this->pdo->prepare('UPDATE cre8shield_catches
                                         SET admin_notes = :note, updated_at = NOW()
                                         WHERE id_catch = :id AND (admin_notes IS NULL OR admin_notes = "")');
            $stmt->execute([
                ':note' => $this->limitText($defaultNote, 1000),
                ':id' => $id,
            ]);
        } catch (Throwable $e) {
            // Non-fatal: the status update already succeeded.
        }
    }

    public function resolveCatch(int $id, int $adminId): bool
    {
        return $this->updateStatus($id, $adminId, 'resolved');
    }

    public function findIdByHash(string $hash): ?int
    {
        $hash = trim($hash);
        if ($hash === '' || !$this->isAvailable()) {
            return null;
        }
        try {
            $stmt = $this->pdo->prepare('SELECT id_catch FROM cre8shield_catches WHERE catch_hash = :h LIMIT 1');
            $stmt->execute([':h' => $hash]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $id = (int) ($row['id_catch'] ?? 0);

            return $id > 0 ? $id : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Build the dedupe hash from logically meaningful fields only.
     * Same source + page + level + sanitized payload + categories => same catch.
     */
    public function computeCatchHash(array $data): string
    {
        $parts = [
            (string) ($data['source_type'] ?? ''),
            (string) ($data['source_id'] ?? ''),
            (string) ($data['page'] ?? ''),
            $this->normalizeRiskLevel((string) ($data['risk_level'] ?? '')),
            (string) ($data['sanitized_message'] ?? ''),
            (string) ($data['risk_categories'] ?? ''),
        ];

        return hash('sha256', implode('|', $parts));
    }

    private function updateStatus(int $id, int $adminId, string $status): bool
    {
        if ($id <= 0 || !$this->isAvailable()) {
            return false;
        }
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            return false;
        }
        try {
            $stmt = $this->pdo->prepare('UPDATE cre8shield_catches
                                         SET status = :st, reviewed_by = :ab, reviewed_at = NOW(), updated_at = NOW()
                                         WHERE id_catch = :id');

            return (bool) $stmt->execute([
                ':st' => $status,
                ':ab' => $adminId > 0 ? $adminId : null,
                ':id' => $id,
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }

    private function normalizeRiskLevel(string $level): string
    {
        $level = strtolower(trim($level));

        return in_array($level, self::ALLOWED_RISK_LEVELS, true) ? $level : '';
    }

    private function normalizeRowForInsert(array $data, string $riskLevel): array
    {
        $cats = $data['risk_categories'] ?? [];
        if (is_array($cats)) {
            $cats = implode(',', array_map(static fn ($c) => preg_replace('/[^a-z0-9_\-]/i', '', (string) $c), $cats));
        }
        $cats = trim((string) $cats);

        $reporterUserId = isset($data['reporter_user_id']) && (int) $data['reporter_user_id'] > 0 ? (int) $data['reporter_user_id'] : null;
        $reportedUserId = isset($data['reported_user_id']) && (int) $data['reported_user_id'] > 0 ? (int) $data['reported_user_id'] : null;
        $score = (int) ($data['risk_score'] ?? 0);
        if ($score < 0) {
            $score = 0;
        }
        if ($score > 100) {
            $score = 100;
        }

        return [
            'risk_level' => $riskLevel,
            'risk_score' => $score,
            'risk_categories' => $this->limitText($cats, 800),
            'finding_summary' => $this->limitText((string) ($data['finding_summary'] ?? ''), 4000),
            'safe_recommendations' => $this->limitText((string) ($data['safe_recommendations'] ?? ''), 2000),
            'raw_message_snapshot' => $this->limitText((string) ($data['raw_message_snapshot'] ?? ''), 4000),
            'sanitized_message' => $this->limitText((string) ($data['sanitized_message'] ?? ''), 4000),
            'source_type' => $this->limitText((string) ($data['source_type'] ?? 'chat_message'), 40),
            'source_id' => $this->nullableText($data['source_id'] ?? null, 64),
            'source_label' => $this->nullableText($data['source_label'] ?? null, 255),
            'page' => $this->nullableText($data['page'] ?? null, 80),
            'mode' => $this->nullableText($data['mode'] ?? null, 80),
            'role' => $this->nullableText($data['role'] ?? null, 40),
            'reporter_user_id' => $reporterUserId,
            'reporter_role' => $this->nullableText($data['reporter_role'] ?? null, 40),
            'reported_user_id' => $reportedUserId,
            'reported_role' => $this->nullableText($data['reported_role'] ?? null, 40),
            'ai_decision' => $this->nullableText($data['ai_decision'] ?? null, 32),
            'ai_rationale' => $this->nullableText($data['ai_rationale'] ?? null, 2000),
            'status' => $this->normalizeStatus((string) ($data['status'] ?? 'open')),
            'admin_notes' => $this->nullableText($data['admin_notes'] ?? null, 1000),
        ];
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return in_array($status, self::ALLOWED_STATUSES, true) ? $status : 'open';
    }

    private function limitText(string $text, int $max): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        if (function_exists('mb_substr') && mb_strlen($text) > $max) {
            return mb_substr($text, 0, $max);
        }
        if (strlen($text) > $max) {
            return substr($text, 0, $max);
        }

        return $text;
    }

    /** @param mixed $value */
    private function nullableText($value, int $max): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = $this->limitText((string) $value, $max);

        return $s === '' ? null : $s;
    }

    public function isAvailable(): bool
    {
        if ($this->tableAvailable !== null) {
            return $this->tableAvailable;
        }
        try {
            $stmt = $this->pdo->query('SHOW TABLES LIKE ' . $this->pdo->quote('cre8shield_catches'));
            $row = $stmt ? $stmt->fetchColumn() : false;
            $this->tableAvailable = (bool) $row;
        } catch (Throwable $e) {
            $this->tableAvailable = false;
        }

        return $this->tableAvailable;
    }
}
