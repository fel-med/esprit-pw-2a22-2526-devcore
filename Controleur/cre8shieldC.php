<?php

require_once __DIR__ . '/../Modele/cre8shieldCatch.php';

/**
 * Cre8ShieldC — thin controller around Cre8ShieldCatch.
 *
 * Used by:
 *   - the BackOffice monitor page (Vue/BackOffice/cre8shield/index.php) for listing and
 *     mark-reviewed/ignore/escalate/resolve actions.
 *   - CondidatureC (handleCre8ShieldCre8PilotRequest, handleCre8PilotPageScanRequest)
 *     to persist medium/high catches with dedupe.
 *
 * It intentionally never deletes rows, never weakens Cre8Shield, and never punishes users.
 */
class Cre8ShieldC
{
    private Cre8ShieldCatch $catches;

    public function __construct(?Cre8ShieldCatch $catches = null)
    {
        $this->catches = $catches ?: new Cre8ShieldCatch();
    }

    public function getCatchesModel(): Cre8ShieldCatch
    {
        return $this->catches;
    }

    public function isAvailable(): bool
    {
        return $this->catches->isAvailable();
    }

    public function listByRisk(string $riskLevel, int $limit = 100, int $offset = 0): array
    {
        return $this->catches->listByRisk($riskLevel, $limit, $offset);
    }

    public function listReviewed(int $limit = 100, int $offset = 0): array
    {
        return $this->catches->listReviewed($limit, $offset);
    }

    public function listEscalated(int $limit = 100, int $offset = 0): array
    {
        return $this->catches->listByStatus('escalated', $limit, $offset);
    }

    public function getCatchById(int $id): ?array
    {
        return $this->catches->getCatchById($id);
    }

    public function markReviewed(int $id, int $adminId): bool
    {
        return $this->catches->markReviewed($id, $adminId);
    }

    public function ignoreCatch(int $id, int $adminId): bool
    {
        return $this->catches->ignoreCatch($id, $adminId);
    }

    public function escalateCatch(int $id, int $adminId): bool
    {
        return $this->catches->escalateCatch($id, $adminId);
    }

    public function resolveCatch(int $id, int $adminId): bool
    {
        return $this->catches->resolveCatch($id, $adminId);
    }

    public function createCatchIfNotDuplicate(array $data): array
    {
        return $this->catches->createCatchIfNotDuplicate($data);
    }

    public function getMonitorCounts(): array
    {
        return [
            'high' => $this->catches->countByRiskAndOpen('high'),
            'medium' => $this->catches->countByRiskAndOpen('medium'),
            'reviewed' => $this->catches->countByStatuses(['reviewed', 'resolved', 'ignored']),
            'escalated' => $this->catches->countByStatuses(['escalated']),
        ];
    }
}
