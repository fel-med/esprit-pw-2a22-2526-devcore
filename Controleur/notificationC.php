<?php
require_once __DIR__ . '/../config.php';

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
