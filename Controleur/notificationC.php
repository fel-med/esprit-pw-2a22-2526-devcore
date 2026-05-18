<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/session_helper.php';

class NotificationC
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?: Config::getConnexion();
    }

    public function createNotification(
        $idUtilisateur,
        $typeAction,
        $titre,
        $message,
        $lien = null,
        $sourceType = null,
        $idSource = null,
        $idActeur = null,
        $roleActeur = null,
        $cleAction = null,
        $donnees = null
    ) {
        $idUtilisateur = (int) $idUtilisateur;
        if ($idUtilisateur <= 0) {
            return false;
        }

        $cleAction = $cleAction !== null && trim((string) $cleAction) !== '' ? trim((string) $cleAction) : null;
        if ($cleAction !== null) {
            $existing = $this->findIdByActionKey($cleAction);
            if ($existing > 0) {
                return $existing;
            }
        }

        $donneesJson = null;
        if (is_array($donnees)) {
            $donneesJson = json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif ($donnees !== null && trim((string) $donnees) !== '') {
            $donneesJson = (string) $donnees;
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO notification_actions (
                idUtilisateur,
                idActeur,
                roleActeur,
                typeAction,
                titre,
                message,
                lien,
                sourceType,
                idSource,
                cleAction,
                donneesJson,
                estLu,
                dateCreation,
                dateLecture
            ) VALUES (
                :idUtilisateur,
                :idActeur,
                :roleActeur,
                :typeAction,
                :titre,
                :message,
                :lien,
                :sourceType,
                :idSource,
                :cleAction,
                :donneesJson,
                0,
                NOW(),
                NULL
            )
        ');

        $success = $stmt->execute([
            'idUtilisateur' => $idUtilisateur,
            'idActeur' => $idActeur !== null && $idActeur !== '' ? (int) $idActeur : null,
            'roleActeur' => $roleActeur !== null && trim((string) $roleActeur) !== '' ? trim((string) $roleActeur) : null,
            'typeAction' => trim((string) $typeAction) !== '' ? trim((string) $typeAction) : 'notification',
            'titre' => trim((string) $titre) !== '' ? trim((string) $titre) : 'Notification',
            'message' => trim((string) $message),
            'lien' => $lien !== null && trim((string) $lien) !== '' ? trim((string) $lien) : null,
            'sourceType' => $sourceType !== null && trim((string) $sourceType) !== '' ? trim((string) $sourceType) : null,
            'idSource' => $idSource !== null && trim((string) $idSource) !== '' ? trim((string) $idSource) : null,
            'cleAction' => $cleAction,
            'donneesJson' => $donneesJson,
        ]);

        if (!$success) {
            return false;
        }

        return (int) $this->pdo->lastInsertId() ?: true;
    }

    public function getNotificationsForUser($idUtilisateur, $limit = 20, $onlyUnread = false): array
    {
        $idUtilisateur = (int) $idUtilisateur;
        if ($idUtilisateur <= 0) {
            return [];
        }

        $limit = max(1, min(50, (int) $limit));
        $sql = '
            SELECT
                idNotificationAction,
                idUtilisateur,
                idActeur,
                roleActeur,
                typeAction,
                titre,
                message,
                lien,
                sourceType,
                idSource,
                cleAction,
                donneesJson,
                estLu,
                dateCreation,
                dateLecture
            FROM notification_actions
            WHERE idUtilisateur = :idUtilisateur
        ';

        if ($onlyUnread) {
            $sql .= ' AND estLu = 0 ';
        }

        $sql .= ' ORDER BY dateCreation DESC, idNotificationAction DESC LIMIT :limit';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':idUtilisateur', $idUtilisateur, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([$this, 'normalizeNotificationRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function countUnread($idUtilisateur): int
    {
        $idUtilisateur = (int) $idUtilisateur;
        if ($idUtilisateur <= 0) {
            return 0;
        }

        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) AS unreadCount
            FROM notification_actions
            WHERE idUtilisateur = :idUtilisateur
              AND estLu = 0
        ');
        $stmt->execute(['idUtilisateur' => $idUtilisateur]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return (int) ($row['unreadCount'] ?? 0);
    }

    public function markAsRead($idUtilisateur, array $ids): bool
    {
        $idUtilisateur = (int) $idUtilisateur;
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($id) => $id > 0)));

        if ($idUtilisateur <= 0 || empty($ids)) {
            return true;
        }

        $placeholders = [];
        $params = ['idUtilisateur' => $idUtilisateur];
        foreach ($ids as $index => $id) {
            $key = 'id' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
        }

        $stmt = $this->pdo->prepare('
            UPDATE notification_actions
            SET estLu = 1,
                dateLecture = NOW()
            WHERE idUtilisateur = :idUtilisateur
              AND estLu = 0
              AND idNotificationAction IN (' . implode(',', $placeholders) . ')
        ');

        return $stmt->execute($params);
    }

    public function markAllAsRead($idUtilisateur): bool
    {
        $idUtilisateur = (int) $idUtilisateur;
        if ($idUtilisateur <= 0) {
            return false;
        }

        $stmt = $this->pdo->prepare('
            UPDATE notification_actions
            SET estLu = 1,
                dateLecture = NOW()
            WHERE idUtilisateur = :idUtilisateur
              AND estLu = 0
        ');

        return $stmt->execute(['idUtilisateur' => $idUtilisateur]);
    }

    public function normalizeNotificationRow($row): array
    {
        $row = is_array($row) ? $row : [];
        $row['idNotificationAction'] = (int) ($row['idNotificationAction'] ?? 0);
        $row['idUtilisateur'] = (int) ($row['idUtilisateur'] ?? 0);
        $row['idActeur'] = isset($row['idActeur']) ? (int) $row['idActeur'] : null;
        $row['idSource'] = isset($row['idSource']) ? trim((string) $row['idSource']) : null;
        $row['typeAction'] = trim((string) ($row['typeAction'] ?? 'notification'));
        $row['titre'] = trim((string) ($row['titre'] ?? '')) ?: 'Notification';
        $row['message'] = trim((string) ($row['message'] ?? ''));
        $row['lien'] = trim((string) ($row['lien'] ?? ''));
        $row['sourceType'] = trim((string) ($row['sourceType'] ?? ''));
        $row['cleAction'] = trim((string) ($row['cleAction'] ?? ''));
        $row['donneesJson'] = $row['donneesJson'] ?? null;
        $row['estLu'] = (int) ($row['estLu'] ?? 0);
        $row['isUnread'] = $row['estLu'] === 0;
        $row['dateCreation'] = $row['dateCreation'] ?? null;
        $row['dateLecture'] = $row['dateLecture'] ?? null;

        return $row;
    }

    public function getBackOfficeUserIdsByRoles(array $roles, ?int $excludeUserId = null): array
    {
        $roles = array_values(array_unique(array_filter(array_map(static function ($role) {
            return cc_normalize_role($role);
        }, $roles), static function ($role) {
            return in_array($role, ['admin', 'super_admin', 'hyper_admin'], true);
        })));

        if (empty($roles)) {
            return [];
        }

        $params = [];
        $placeholders = [];
        foreach ($roles as $index => $role) {
            $key = 'role' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $role;
        }

        $sql = "
            SELECT id
            FROM utilisateur
            WHERE role IN (" . implode(',', $placeholders) . ")
              AND statut = 'actif'
        ";

        if ($excludeUserId !== null && $excludeUserId > 0) {
            $sql .= ' AND id <> :excludeUserId';
            $params['excludeUserId'] = $excludeUserId;
        }

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return array_values(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)));
    }

    public function getComplaintNotificationRecipientIds(string $complainantRole, int $complainantId): array
    {
        $complainantRole = cc_normalize_role($complainantRole);

        $candidateRoles = ['admin', 'super_admin', 'hyper_admin'];
        $roles = array_values(array_filter($candidateRoles, static function ($viewerRole) use ($complainantRole) {
            return cc_can_view_reclamation_from_role($viewerRole, $complainantRole);
        }));

        return $this->getBackOfficeUserIdsByRoles($roles, $complainantId > 0 ? $complainantId : null);
    }

    public function notifyUsers(
        array $recipientIds,
        string $typeAction,
        string $title,
        string $message,
        ?string $link = null,
        ?string $sourceType = null,
        $idSource = null,
        $idActeur = null,
        ?string $roleActeur = null,
        ?string $cleActionPrefix = null,
        $donnees = null
    ): void {
        foreach (array_values(array_unique(array_map('intval', $recipientIds))) as $recipientId) {
            if ($recipientId <= 0) {
                continue;
            }

            $cleAction = null;
            if ($cleActionPrefix !== null && trim($cleActionPrefix) !== '') {
                $cleAction = rtrim($cleActionPrefix, '_') . '_user_' . $recipientId;
            }

            $this->createNotification(
                $recipientId,
                $typeAction,
                $title,
                $message,
                $link,
                $sourceType,
                $idSource,
                $idActeur,
                $roleActeur,
                $cleAction,
                $donnees
            );
        }
    }

    public function notifyComplaintCreated($reclamationId, $complainantId, $complainantRole, $description, bool $isSuspensionAppeal): void
    {
        try {
            $reclamationId = (int) $reclamationId;
            $complainantId = (int) $complainantId;
            $complainantRole = cc_normalize_role($complainantRole);

            if ($reclamationId <= 0 || $complainantId <= 0 || $complainantRole === '') {
                return;
            }

            $recipients = $this->getComplaintNotificationRecipientIds($complainantRole, $complainantId);
            if (empty($recipients)) {
                return;
            }

            $typeAction = $isSuspensionAppeal ? 'suspension_appeal_new' : 'complaint_new';
            $title = $isSuspensionAppeal ? 'Suspension appeal submitted' : 'New complaint';
            $message = $this->notificationExcerpt((string) $description);
            $link = function_exists('cc_app_url')
                ? cc_app_url('Vue/BackOffice/utilisateur/reclamations.php')
                : 'Vue/BackOffice/utilisateur/reclamations.php';

            $this->notifyUsers(
                $recipients,
                $typeAction,
                $title,
                $message,
                $link,
                'reclamation',
                $reclamationId,
                $complainantId,
                $complainantRole,
                $typeAction . '_' . $reclamationId,
                [
                    'reclamation_id' => $reclamationId,
                    'complainant_role' => $complainantRole,
                    'is_suspension_appeal' => $isSuspensionAppeal,
                ]
            );
        } catch (Throwable $e) {
            error_log('Complaint notification fan-out failed: ' . $e->getMessage());
        }
    }

    public function getAdminRequestRecipientIds($receiverScope, $receiverId = null, $excludeSenderId = null): array
    {
        $receiverScope = strtolower(trim((string) $receiverScope));
        $excludeSenderId = is_numeric($excludeSenderId) && (int) $excludeSenderId > 0 ? (int) $excludeSenderId : null;

        if ($receiverScope === 'super_admins') {
            return $this->getBackOfficeUserIdsByRoles(['super_admin', 'hyper_admin'], $excludeSenderId);
        }

        if ($receiverScope === 'hyper_admins') {
            return $this->getBackOfficeUserIdsByRoles(['hyper_admin'], $excludeSenderId);
        }

        if ($receiverScope === 'specific_admin') {
            $receiverId = is_numeric($receiverId) && (int) $receiverId > 0 ? (int) $receiverId : 0;
            if ($receiverId <= 0 || ($excludeSenderId !== null && $receiverId === $excludeSenderId)) {
                return [];
            }

            $stmt = $this->pdo->prepare("
                SELECT id
                FROM utilisateur
                WHERE id = :id
                  AND statut = 'actif'
                  AND role IN ('admin', 'super_admin', 'hyper_admin')
                LIMIT 1
            ");
            $stmt->execute(['id' => $receiverId]);
            $id = (int) ($stmt->fetchColumn() ?: 0);

            return $id > 0 ? [$id] : [];
        }

        return [];
    }

    public function notifyAdminRequestReceived(
        $requestId,
        $senderId,
        $senderRole,
        $receiverScope,
        $receiverId,
        $requestType,
        $title,
        $message
    ): void {
        try {
            $requestId = (int) $requestId;
            $senderId = (int) $senderId;
            $senderRole = cc_normalize_role($senderRole);

            if ($requestId <= 0 || $senderId <= 0) {
                return;
            }

            $recipients = $this->getAdminRequestRecipientIds($receiverScope, $receiverId, $senderId);
            if (empty($recipients)) {
                return;
            }

            $link = function_exists('cc_app_url')
                ? cc_app_url('Vue/BackOffice/utilisateur/admin_requests.php')
                : 'Vue/BackOffice/utilisateur/admin_requests.php';
            $requestTitle = trim((string) $title) ?: 'Admin request';
            $body = $this->notificationExcerpt($requestTitle . ' - from ' . $senderRole . '. ' . (string) $message);

            $this->notifyUsers(
                $recipients,
                'admin_request_received',
                'New admin request',
                $body,
                $link,
                'admin_request',
                $requestId,
                $senderId,
                $senderRole,
                'admin_request_received_' . $requestId,
                [
                    'request_id' => $requestId,
                    'request_type' => (string) $requestType,
                    'receiver_scope' => (string) $receiverScope,
                ]
            );
        } catch (Throwable $e) {
            error_log('Admin request received notification failed: ' . $e->getMessage());
        }
    }

    public function notifyAdminRequestStatusUpdated(
        $requestId,
        $senderId,
        $handledBy,
        $handledByRole,
        $newStatus,
        $title,
        $responseMessage = null
    ): void {
        try {
            $requestId = (int) $requestId;
            $senderId = (int) $senderId;
            $handledBy = is_numeric($handledBy) ? (int) $handledBy : null;
            $handledByRole = cc_normalize_role($handledByRole);
            $newStatus = strtolower(trim((string) $newStatus));

            if ($requestId <= 0 || $senderId <= 0 || !in_array($newStatus, ['approved', 'refused', 'done'], true)) {
                return;
            }

            $requestTitle = trim((string) $title) ?: 'Admin request';
            $message = 'Your request "' . $requestTitle . '" was ' . $newStatus . '.';
            $responseMessage = trim((string) $responseMessage);
            if ($responseMessage !== '') {
                $message .= ' ' . $this->notificationExcerpt($responseMessage, 90);
            }

            $link = function_exists('cc_app_url')
                ? cc_app_url('Vue/BackOffice/utilisateur/admin_requests.php')
                : 'Vue/BackOffice/utilisateur/admin_requests.php';

            $this->createNotification(
                $senderId,
                'admin_request_status_updated',
                'Admin request updated',
                $message,
                $link,
                'admin_request',
                $requestId,
                $handledBy,
                $handledByRole,
                'admin_request_status_' . $requestId . '_' . $newStatus . '_sender_' . $senderId,
                [
                    'request_id' => $requestId,
                    'status' => $newStatus,
                ]
            );
        } catch (Throwable $e) {
            error_log('Admin request status notification failed: ' . $e->getMessage());
        }
    }

    private function notificationExcerpt(string $description, int $limit = 140): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($description)) ?? '');
        if ($text === '') {
            return 'A complaint was submitted.';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($text, 'UTF-8') > $limit
                ? mb_substr($text, 0, $limit - 1, 'UTF-8') . '...'
                : $text;
        }

        return strlen($text) > $limit ? substr($text, 0, $limit - 1) . '...' : $text;
    }


    public function getNotificationsForUserFiltered($idUtilisateur, string $status = 'all', string $category = 'all', int $limit = 120): array
    {
        $idUtilisateur = (int) $idUtilisateur;
        if ($idUtilisateur <= 0) {
            return [];
        }

        $status = in_array($status, ['all', 'unread', 'read'], true) ? $status : 'all';
        $category = in_array($category, ['all', 'posts', 'collaboration', 'admin', 'complaints', 'events'], true) ? $category : 'all';
        $limit = max(1, min(200, (int) $limit));

        $params = ['idUtilisateur' => $idUtilisateur];
        $where = ['idUtilisateur = :idUtilisateur'];

        if ($status === 'unread') {
            $where[] = 'estLu = 0';
        } elseif ($status === 'read') {
            $where[] = 'estLu = 1';
        }

        $types = $this->notificationTypesForCategory($category);
        if (!empty($types)) {
            $placeholders = [];
            foreach ($types as $index => $type) {
                $key = 'type' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $type;
            }
            $where[] = 'typeAction IN (' . implode(',', $placeholders) . ')';
        }

        $sql = '
            SELECT
                idNotificationAction,
                idUtilisateur,
                idActeur,
                roleActeur,
                typeAction,
                titre,
                message,
                lien,
                sourceType,
                idSource,
                cleAction,
                donneesJson,
                estLu,
                dateCreation,
                dateLecture
            FROM notification_actions
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY dateCreation DESC, idNotificationAction DESC
            LIMIT :limit
        ';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([$this, 'normalizeNotificationRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function countNotificationsForUser($idUtilisateur, string $status = 'all', string $category = 'all'): int
    {
        $idUtilisateur = (int) $idUtilisateur;
        if ($idUtilisateur <= 0) {
            return 0;
        }

        $status = in_array($status, ['all', 'unread', 'read'], true) ? $status : 'all';
        $category = in_array($category, ['all', 'posts', 'collaboration', 'admin', 'complaints', 'events'], true) ? $category : 'all';

        $params = ['idUtilisateur' => $idUtilisateur];
        $where = ['idUtilisateur = :idUtilisateur'];

        if ($status === 'unread') {
            $where[] = 'estLu = 0';
        } elseif ($status === 'read') {
            $where[] = 'estLu = 1';
        }

        $types = $this->notificationTypesForCategory($category);
        if (!empty($types)) {
            $placeholders = [];
            foreach ($types as $index => $type) {
                $key = 'type' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $type;
            }
            $where[] = 'typeAction IN (' . implode(',', $placeholders) . ')';
        }

        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) AS total
            FROM notification_actions
            WHERE ' . implode(' AND ', $where)
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return (int) ($row['total'] ?? 0);
    }

    private function notificationTypesForCategory(string $category): array
    {
        return match ($category) {
            'posts' => ['post_comment', 'post_reaction'],
            'collaboration' => [
                'offer_invitation',
                'offer_accepted',
                'offer_refused',
                'candidature_received',
                'candidature_accepted',
                'candidature_refused',
                'negotiation_message',
            ],
            'admin' => ['admin_post_removed', 'admin_product_removed'],
            'complaints' => ['complaint_answered'],
            'events' => ['event_today'],
            default => [],
        };
    }

    private function findIdByActionKey(string $cleAction): int
    {
        $stmt = $this->pdo->prepare('
            SELECT idNotificationAction
            FROM notification_actions
            WHERE cleAction = :cleAction
            LIMIT 1
        ');
        $stmt->execute(['cleAction' => $cleAction]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return (int) ($row['idNotificationAction'] ?? 0);
    }
}
