<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Modele/utilisateur.php';
require_once __DIR__ . '/session_helper.php';
require_once __DIR__ . '/adminAuditC.php';
require_once __DIR__ . '/notificationC.php';
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;   
class UtilisateurC {

    private function utilisateurColumns(PDO $db): array
    {
        static $columns = null;
        if ($columns !== null) {
            return $columns;
        }

        $columns = [];
        try {
            $stmt = $db->query("SHOW COLUMNS FROM utilisateur");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $field = (string)($row['Field'] ?? '');
                if ($field !== '') {
                    $columns[$field] = true;
                }
            }
        } catch (Throwable $e) {
            error_log('Utilisateur column inspection failed: ' . $e->getMessage());
        }

        return $columns;
    }

    private function utilisateurHasColumn(PDO $db, string $column): bool
    {
        $columns = $this->utilisateurColumns($db);
        return isset($columns[$column]);
    }

    private function tableColumns(PDO $db, string $table): array
    {
        static $cache = [];
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        if ($table === '') {
            return [];
        }
        if (array_key_exists($table, $cache)) {
            return $cache[$table];
        }

        $cache[$table] = [];
        try {
            $stmt = $db->query("SHOW COLUMNS FROM `$table`");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $field = (string)($row['Field'] ?? '');
                if ($field !== '') {
                    $cache[$table][$field] = true;
                }
            }
        } catch (Throwable $e) {
            error_log('Table column inspection failed for ' . $table . ': ' . $e->getMessage());
        }

        return $cache[$table];
    }

    private function restoreLogColumns(PDO $db): array
    {
        return $this->tableColumns($db, 'account_restore_logs');
    }

    private function accountEmailLogColumns(PDO $db): array
    {
        return $this->tableColumns($db, 'account_email_logs');
    }

    private function userSelectColumns(PDO $db): string
    {
        $columns = [
            'id',
            'nom',
            'email',
            'role',
            'statut',
            'suspended_by',
            'suspended_by_role',
            'suspended_at',
            'suspension_reason',
        ];

        foreach (['deleted_at', 'deleted_by', 'deleted_by_role', 'delete_reason', 'restored_at', 'restored_by', 'restored_by_role'] as $column) {
            if ($this->utilisateurHasColumn($db, $column)) {
                $columns[] = $column;
            }
        }

        return implode(', ', $columns);
    }

    private function activeUserFilter(PDO $db): string
    {
        return $this->utilisateurHasColumn($db, 'deleted_at') ? ' AND deleted_at IS NULL' : '';
    }

    public function userSoftDeleteColumnsReady(): bool
    {
        $db = config::getConnexion();
        foreach (['deleted_at', 'deleted_by', 'deleted_by_role', 'delete_reason', 'restored_at', 'restored_by', 'restored_by_role'] as $column) {
            if (!$this->utilisateurHasColumn($db, $column)) {
                return false;
            }
        }
        return true;
    }

    private function resolveActorId($actorId): int
    {
        if (is_numeric($actorId) && (int)$actorId > 0) {
            return (int)$actorId;
        }

        return function_exists('cc_current_user_id') ? (int)cc_current_user_id() : 0;
    }

    private function resolveActorRole($actorRole): string
    {
        $role = trim((string)$actorRole);
        if ($role !== '') {
            return function_exists('cc_normalize_role') ? cc_normalize_role($role) : strtolower($role);
        }

        return function_exists('cc_current_user_role') ? cc_current_user_role() : '';
    }

    private function envValue(array $keys): string
    {
        foreach ($keys as $key) {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
            if ($value !== false && trim((string)$value) !== '') {
                return trim((string)$value);
            }
        }

        return '';
    }

    private function safeMailError(Throwable $e, ?PHPMailer $mail = null): string
    {
        $message = $mail && trim((string)$mail->ErrorInfo) !== '' ? (string)$mail->ErrorInfo : $e->getMessage();
        $message = preg_replace('/\s+/', ' ', strip_tags($message)) ?? 'Email delivery failed.';
        return substr($message, 0, 250);
    }

    private function configureRestoreMailer(PHPMailer $mail): void
    {
        $host = $this->envValue(['CRE8CONNECT_SMTP_HOST', 'SMTP_HOST', 'MAIL_HOST']);
        $username = $this->envValue(['CRE8CONNECT_SMTP_USERNAME', 'SMTP_USERNAME', 'MAIL_USERNAME']);
        $password = $this->envValue(['CRE8CONNECT_SMTP_PASSWORD', 'SMTP_PASSWORD', 'MAIL_PASSWORD']);
        $port = (int)($this->envValue(['CRE8CONNECT_SMTP_PORT', 'SMTP_PORT', 'MAIL_PORT']) ?: 587);
        $secure = $this->envValue(['CRE8CONNECT_SMTP_SECURE', 'SMTP_SECURE', 'MAIL_ENCRYPTION']) ?: 'tls';
        $fromEmail = $this->envValue(['CRE8CONNECT_MAIL_FROM', 'MAIL_FROM_ADDRESS', 'SMTP_FROM_EMAIL']) ?: $username;
        $fromName = $this->envValue(['CRE8CONNECT_MAIL_FROM_NAME', 'MAIL_FROM_NAME']) ?: 'Cre8Connect';

        if ($host === '' || $username === '' || $password === '' || $fromEmail === '') {
            throw new RuntimeException('Restore email SMTP is not configured.');
        }

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->SMTPSecure = $secure;
        $mail->Port = $port;
        $mail->setFrom($fromEmail, $fromName);
    }

    private function sendAccountRestoreEmail(array $user): array
    {
        return $this->sendAccountStatusEmail($user, 'restored');
    }

    private function accountStatusEmailContent(string $eventType): array
    {
        return match ($eventType) {
            'suspended' => [
                'subject' => 'Your Cre8Connect account has been suspended',
                'html' => 'Your Cre8Connect account has been suspended. You can submit an appeal or complaint to the administration if you believe this needs review.',
                'text' => "Your Cre8Connect account has been suspended.\nYou can submit an appeal or complaint to the administration if you believe this needs review.",
            ],
            'deleted' => [
                'subject' => 'Your Cre8Connect account has been disabled',
                'html' => 'Your Cre8Connect account has been disabled. You can submit an appeal or complaint to the administration if you believe this needs review.',
                'text' => "Your Cre8Connect account has been disabled.\nYou can submit an appeal or complaint to the administration if you believe this needs review.",
            ],
            'reactivated' => [
                'subject' => 'Your Cre8Connect account has been reactivated',
                'html' => 'Your Cre8Connect account has been reactivated. You can log in again using your existing credentials.',
                'text' => "Your Cre8Connect account has been reactivated.\nYou can log in again using your existing credentials.",
            ],
            'profile_updated' => [
                'subject' => 'Your Cre8Connect account information was updated',
                'html' => 'Your Cre8Connect account information was updated.',
                'text' => 'Your Cre8Connect account information was updated.',
            ],
            'email_changed' => [
                'subject' => 'Your Cre8Connect account email was changed',
                'html' => 'Your Cre8Connect account email was changed.',
                'text' => 'Your Cre8Connect account email was changed.',
            ],
            'role_changed' => [
                'subject' => 'Your Cre8Connect account role was updated',
                'html' => 'Your Cre8Connect account role was updated.',
                'text' => 'Your Cre8Connect account role was updated.',
            ],
            default => [
                'subject' => 'Your Cre8Connect account has been restored',
                'html' => 'Your Cre8Connect account has been restored. You can log in again using your existing credentials.',
                'text' => "Your Cre8Connect account has been restored.\nYou can log in again using your existing credentials.",
            ],
        };
    }

    private function accountEmailEventTypes(): array
    {
        return ['suspended', 'deleted', 'reactivated', 'restored', 'profile_updated', 'email_changed', 'role_changed'];
    }

    public function sendAccountStatusEmail(array $user, string $eventType, ?string $reason = null): array
    {
        $eventType = strtolower(trim($eventType));
        if (!in_array($eventType, $this->accountEmailEventTypes(), true)) {
            return ['success' => false, 'error' => 'Unsupported account email event.'];
        }

        $email = trim((string)($user['email'] ?? ''));
        if ($email === '') {
            return ['success' => false, 'error' => 'User has no email address.'];
        }

        $mail = new PHPMailer(true);
        try {
            $this->configureRestoreMailer($mail);
            $name = trim((string)($user['nom'] ?? ''));
            $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $greetingHtml = $name !== '' ? 'Hello ' . $safeName . ',' : 'Hello,';
            $greetingText = $name !== '' ? 'Hello ' . $name . ',' : 'Hello,';
            $content = $this->accountStatusEmailContent($eventType);
            $detail = trim((string)($reason ?? ''));
            $detailHtml = $detail !== '' ? '<p>' . htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') . '</p>' : '';
            $contactHtml = in_array($eventType, ['profile_updated', 'email_changed', 'role_changed'], true)
                ? '<p>If you did not expect this change, please contact the administration through the complaint/appeal page.</p>'
                : '';
            $contactText = in_array($eventType, ['profile_updated', 'email_changed', 'role_changed'], true)
                ? "\nIf you did not expect this change, please contact the administration through the complaint/appeal page."
                : '';

            $mail->addAddress($email, $name !== '' ? $name : $email);
            $mail->isHTML(true);
            $mail->Subject = $content['subject'];
            $mail->Body = '
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Arial,sans-serif;color:#333;">
  <div style="max-width:600px;margin:36px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 5px 15px rgba(0,0,0,.08);">
    <div style="background:linear-gradient(135deg,#9B5DE0,#E11D74);color:#fff;padding:26px 30px;font-size:22px;font-weight:700;">Cre8Connect</div>
    <div style="padding:30px;line-height:1.6;">
      <p>' . $greetingHtml . '</p>
      <p>' . htmlspecialchars($content['html'], ENT_QUOTES, 'UTF-8') . '</p>
      ' . $detailHtml . '
      ' . $contactHtml . '
      <p style="margin-top:24px;color:#6b7280;font-size:13px;">For your security, this email does not include your password.</p>
    </div>
  </div>
</body>
</html>';
            $mail->AltBody = $greetingText . "\n\n" . $content['text'] . ($detail !== '' ? "\n" . $detail : '') . $contactText . "\n\nFor your security, this email does not include your password.";
            $mail->send();

            return ['success' => true, 'error' => null];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $this->safeMailError($e, $mail)];
        }
    }

    private function createAccountEmailLog(PDO $db, array $user, string $eventType, int $actorId, string $actorRole, ?string $reason, array $emailResult): int
    {
        $columnsAvailable = $this->accountEmailLogColumns($db);
        if ($columnsAvailable === []) {
            return 0;
        }

        $success = !empty($emailResult['success']);
        $values = [
            'idUtilisateur' => (int)($user['id'] ?? 0),
            'email' => (string)($user['email'] ?? ''),
            'eventType' => $eventType,
            'actorId' => $actorId > 0 ? $actorId : null,
            'actorRole' => $actorRole !== '' ? $actorRole : null,
            'reason' => $reason !== null && trim($reason) !== '' ? substr(trim($reason), 0, 255) : null,
            'emailStatus' => $success ? 'sent' : 'failed',
            'emailAttempts' => 1,
            'emailError' => $success ? null : (string)($emailResult['error'] ?? 'Email delivery failed.'),
        ];

        $rawNowColumns = ['createdAt', 'lastEmailAttemptAt'];
        if ($success) {
            $rawNowColumns[] = 'emailSentAt';
        }

        $columns = [];
        $placeholders = [];
        $params = [];
        foreach ($values as $column => $value) {
            if (!isset($columnsAvailable[$column])) {
                continue;
            }
            $columns[] = $column;
            $placeholders[] = ':' . $column;
            $params[':' . $column] = $value;
        }
        foreach ($rawNowColumns as $column) {
            if (!isset($columnsAvailable[$column])) {
                continue;
            }
            $columns[] = $column;
            $placeholders[] = 'NOW()';
        }

        if ($columns === []) {
            return 0;
        }

        try {
            $stmt = $db->prepare('INSERT INTO account_email_logs (`' . implode('`, `', $columns) . '`) VALUES (' . implode(', ', $placeholders) . ')');
            $stmt->execute($params);
            return (int)$db->lastInsertId();
        } catch (Throwable $e) {
            error_log('Account email log insert failed: ' . $e->getMessage());
            return 0;
        }
    }

    private function updateAccountEmailLogAttempt(PDO $db, int $emailLogId, array $emailResult): void
    {
        $columnsAvailable = $this->accountEmailLogColumns($db);
        if ($emailLogId <= 0 || $columnsAvailable === []) {
            return;
        }

        $success = !empty($emailResult['success']);
        $sets = [];
        $params = [':idEmailLog' => $emailLogId];

        if (isset($columnsAvailable['emailStatus'])) {
            $sets[] = 'emailStatus = :emailStatus';
            $params[':emailStatus'] = $success ? 'sent' : 'failed';
        }
        if (isset($columnsAvailable['emailAttempts'])) {
            $sets[] = 'emailAttempts = COALESCE(emailAttempts, 0) + 1';
        }
        if (isset($columnsAvailable['lastEmailAttemptAt'])) {
            $sets[] = 'lastEmailAttemptAt = NOW()';
        }
        if (isset($columnsAvailable['emailSentAt']) && $success) {
            $sets[] = 'emailSentAt = NOW()';
        }
        if (isset($columnsAvailable['emailError'])) {
            $sets[] = 'emailError = :emailError';
            $params[':emailError'] = $success ? null : (string)($emailResult['error'] ?? 'Email delivery failed.');
        }

        if ($sets === []) {
            return;
        }

        $stmt = $db->prepare('UPDATE account_email_logs SET ' . implode(', ', $sets) . ' WHERE idEmailLog = :idEmailLog');
        $stmt->execute($params);
    }

    private function logAccountStatusEmailAttempt(PDO $db, array $user, string $eventType, int $actorId, string $actorRole, ?string $reason, ?array $emailResult = null): array
    {
        try {
            $emailResult = $emailResult ?? $this->sendAccountStatusEmail($user, $eventType, $reason);
            $emailLogId = $this->createAccountEmailLog($db, $user, $eventType, $actorId, $actorRole, $reason, $emailResult);
            return ['log_id' => $emailLogId, 'email' => $emailResult];
        } catch (Throwable $e) {
            error_log('Account status email attempt failed: ' . $e->getMessage());
            return ['log_id' => 0, 'email' => ['success' => false, 'error' => $this->safeMailError($e)]];
        }
    }

    private function buildPasswordResetBaseUrl(): string
    {
        foreach (['APP_BASE_URL', 'APP_URL', 'BASE_URL', 'SITE_URL'] as $key) {
            $configuredUrl = trim((string)($_ENV[$key] ?? ''));
            if ($configuredUrl !== '') {
                return rtrim($configuredUrl, '/');
            }
        }

        $forwardedProto = strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
        $scheme = $forwardedProto === 'https'
            || (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
            ? 'https'
            : 'http';

        $host = trim((string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        $projectBasePath = '';

        $frontUserMarker = '/Vue/FrontOffice/utilisateur/';
        $markerPosition = stripos($scriptName, $frontUserMarker);
        if ($markerPosition !== false) {
            $projectBasePath = substr($scriptName, 0, $markerPosition);
        } else {
            $vueMarkerPosition = stripos($scriptName, '/Vue/');
            if ($vueMarkerPosition !== false) {
                $projectBasePath = substr($scriptName, 0, $vueMarkerPosition);
            }
        }

        return rtrim($scheme . '://' . $host . $projectBasePath, '/');
    }

    public function ajouterUser($user) {
        $db = config::getConnexion();

        $check = $db->prepare("SELECT * FROM utilisateur WHERE email=?");
        $check->execute([$user->getEmail()]);
        if ($check->rowCount() > 0) return "Email déjà utilisé";

        $sql = "INSERT INTO utilisateur (nom,email,mot_de_passe,role,statut,tentatives_login,face_descriptor)
        VALUES (?,?,?,?,?,?,?)";

$query = $db->prepare($sql);
$query->execute([
    $user->getNom(),
    $user->getEmail(),
    $user->getMotDePasse(),
    $user->getRole(),
    "actif",
    0,
    $user->getFaceDescriptor() // 👈 AJOUT IMPORTANT
]);

        return "success";
    }
public function updateUser($id, $nom, $email, $role, $actorId = null, $actorRole = null) {
    $db = config::getConnexion();
    $targetId = (int)$id;
    $before = $this->getUserById($targetId);
    $actorId = $this->resolveActorId($actorId);
    $actorRole = $this->resolveActorRole($actorRole);

    $sql = "UPDATE utilisateur SET nom = :nom, email = :email, role = :role WHERE id = :id";
    $req = $db->prepare($sql);
    $req->execute([
        'id' => $targetId,
        'nom' => $nom,
        'email' => $email,
        'role' => $role
        
    ]);

    $after = $this->getUserById($targetId);
    if (!$before || !$after) {
        return;
    }

    $oldName = trim((string)($before['nom'] ?? ''));
    $newName = trim((string)($after['nom'] ?? ''));
    $oldEmail = trim((string)($before['email'] ?? ''));
    $newEmail = trim((string)($after['email'] ?? ''));
    $oldRole = function_exists('cc_normalize_role') ? cc_normalize_role($before['role'] ?? '') : strtolower(trim((string)($before['role'] ?? '')));
    $newRole = function_exists('cc_normalize_role') ? cc_normalize_role($after['role'] ?? '') : strtolower(trim((string)($after['role'] ?? '')));

    if ($oldName !== $newName) {
        $this->logAccountStatusEmailAttempt(
            $db,
            $after,
            'profile_updated',
            $actorId,
            $actorRole,
            'Your account name was changed from ' . ($oldName !== '' ? $oldName : 'empty') . ' to ' . ($newName !== '' ? $newName : 'empty') . '.'
        );
    }

    if ($oldEmail !== $newEmail && $oldEmail !== '') {
        $emailTarget = $after;
        $emailTarget['email'] = $oldEmail;
        $this->logAccountStatusEmailAttempt(
            $db,
            $emailTarget,
            'email_changed',
            $actorId,
            $actorRole,
            'Your account email was changed from ' . $oldEmail . ' to ' . $newEmail . '.'
        );
    }

    if ($oldRole !== $newRole) {
        $this->logAccountStatusEmailAttempt(
            $db,
            $after,
            'role_changed',
            $actorId,
            $actorRole,
            'Your role was changed from ' . $oldRole . ' to ' . $newRole . '.'
        );
    }
}

public function afficherAdminAccounts(array $roles) {
    $db = config::getConnexion();
    $roles = array_values(array_intersect($roles, ['admin', 'super_admin']));
    if (empty($roles)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($roles), '?'));
    $select = $this->userSelectColumns($db);
    $stmt = $db->prepare("
        SELECT $select
        FROM utilisateur
        WHERE role IN ($placeholders)" . $this->activeUserFilter($db) . "
        ORDER BY role DESC, id DESC
    ");
    $stmt->execute($roles);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getUserById($id) {
    $db = config::getConnexion();
    $select = $this->userSelectColumns($db);
    $stmt = $db->prepare("
        SELECT $select
        FROM utilisateur
        WHERE id = ?
    ");
    $stmt->execute([(int)$id]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function getUserByEmail($email) {
    $db = config::getConnexion();
    $select = $this->userSelectColumns($db);
    $stmt = $db->prepare("
        SELECT $select
        FROM utilisateur
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->execute([trim((string)$email)]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function getDeletedUsers(): array {
    $db = config::getConnexion();
    if (!$this->userSoftDeleteColumnsReady()) {
        return [];
    }

    $select = $this->userSelectColumns($db);
    $stmt = $db->query("
        SELECT $select
        FROM utilisateur
        WHERE deleted_at IS NOT NULL
        ORDER BY deleted_at DESC, id DESC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

private function createRestoreNotification(PDO $db, array $user, int $actorId, string $actorRole): ?int
{
    try {
        $targetId = (int)($user['id'] ?? 0);
        if ($targetId <= 0) {
            return null;
        }

        $notificationC = new NotificationC($db);
        $id = $notificationC->createNotification(
            $targetId,
            'account_restored',
            'Account restored',
            'Your Cre8Connect account has been restored. You can log in again.',
            function_exists('cc_app_url') ? cc_app_url('Vue/FrontOffice/utilisateur/login.php') : 'Vue/FrontOffice/utilisateur/login.php',
            'utilisateur',
            $targetId,
            $actorId,
            $actorRole,
            'account_restored_' . $targetId . '_' . time(),
            ['user_id' => $targetId]
        );

        return is_numeric($id) ? (int)$id : null;
    } catch (Throwable $e) {
        error_log('Account restore notification failed: ' . $e->getMessage());
        return null;
    }
}

private function createAccountRestoreLog(PDO $db, array $before, array $after, int $actorId, string $actorRole, string $reason, ?int $notificationId, array $emailResult): int
{
    $columnsAvailable = $this->restoreLogColumns($db);
    if ($columnsAvailable === []) {
        return 0;
    }

    $values = [
        'idUtilisateur' => (int)($after['id'] ?? $before['id'] ?? 0),
        'restoredBy' => $actorId,
        'restoredByRole' => $actorRole,
        'restoreType' => 'undo_delete',
        'oldStatus' => (string)($before['statut'] ?? ''),
        'newStatus' => (string)($after['statut'] ?? ''),
        'reason' => $reason,
        'emailSent' => !empty($emailResult['success']) ? 1 : 0,
        'notificationId' => $notificationId,
        'emailAttempts' => 1,
        'emailStatus' => !empty($emailResult['success']) ? 'sent' : 'failed',
        'emailError' => !empty($emailResult['success']) ? null : (string)($emailResult['error'] ?? 'Email delivery failed.'),
    ];

    $rawNowColumns = ['createdAt'];
    if (!empty($emailResult['success'])) {
        $rawNowColumns[] = 'emailSentAt';
    }
    if (isset($columnsAvailable['lastEmailAttemptAt'])) {
        $rawNowColumns[] = 'lastEmailAttemptAt';
    }

    $columns = [];
    $placeholders = [];
    $params = [];
    foreach ($values as $column => $value) {
        if (!isset($columnsAvailable[$column])) {
            continue;
        }
        $columns[] = $column;
        $placeholders[] = ':' . $column;
        $params[':' . $column] = $value;
    }
    foreach ($rawNowColumns as $column) {
        if (!isset($columnsAvailable[$column])) {
            continue;
        }
        $columns[] = $column;
        $placeholders[] = 'NOW()';
    }

    if ($columns === []) {
        return 0;
    }

    try {
        $sql = 'INSERT INTO account_restore_logs (`' . implode('`, `', $columns) . '`) VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int)$db->lastInsertId();
    } catch (Throwable $e) {
        error_log('Account restore log insert failed: ' . $e->getMessage());
        return 0;
    }
}

private function fetchAccountRestoreLogById(PDO $db, int $restoreLogId): ?array
{
    if ($restoreLogId <= 0 || $this->restoreLogColumns($db) === []) {
        return null;
    }

    $stmt = $db->prepare("SELECT * FROM account_restore_logs WHERE idRestore = :id LIMIT 1");
    $stmt->execute([':id' => $restoreLogId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

private function updateRestoreLogEmailAttempt(PDO $db, int $restoreLogId, array $emailResult): void
{
    $columnsAvailable = $this->restoreLogColumns($db);
    if ($restoreLogId <= 0 || $columnsAvailable === []) {
        return;
    }

    $sets = [];
    $params = [':idRestore' => $restoreLogId];
    $success = !empty($emailResult['success']);

    if (isset($columnsAvailable['emailSent'])) {
        $sets[] = 'emailSent = :emailSent';
        $params[':emailSent'] = $success ? 1 : 0;
    }
    if (isset($columnsAvailable['emailSentAt']) && $success) {
        $sets[] = 'emailSentAt = NOW()';
    }
    if (isset($columnsAvailable['emailAttempts'])) {
        $sets[] = 'emailAttempts = COALESCE(emailAttempts, 0) + 1';
    }
    if (isset($columnsAvailable['lastEmailAttemptAt'])) {
        $sets[] = 'lastEmailAttemptAt = NOW()';
    }
    if (isset($columnsAvailable['emailStatus'])) {
        $sets[] = 'emailStatus = :emailStatus';
        $params[':emailStatus'] = $success ? 'sent' : 'failed';
    }
    if (isset($columnsAvailable['emailError'])) {
        $sets[] = 'emailError = :emailError';
        $params[':emailError'] = $success ? null : (string)($emailResult['error'] ?? 'Email delivery failed.');
    }

    if ($sets === []) {
        return;
    }

    $stmt = $db->prepare('UPDATE account_restore_logs SET ' . implode(', ', $sets) . ' WHERE idRestore = :idRestore');
    $stmt->execute($params);
}

public function getAccountRestoreLogs(): array
{
    $db = config::getConnexion();
    $logColumns = $this->restoreLogColumns($db);
    if ($logColumns === []) {
        return [];
    }

    $select = [
        'l.idRestore',
        'l.idUtilisateur',
        'l.restoredBy',
        'l.restoredByRole',
        'l.restoreType',
        'l.oldStatus',
        'l.newStatus',
        'l.reason',
        'l.emailSent',
        'l.emailSentAt',
        'l.notificationId',
        'l.createdAt',
        'u.nom',
        'u.email',
        'u.role',
    ];

    foreach (['restored_at', 'restored_by', 'restored_by_role'] as $userColumn) {
        $select[] = $this->utilisateurHasColumn($db, $userColumn) ? 'u.' . $userColumn : 'NULL AS ' . $userColumn;
    }

    foreach (['emailAttempts', 'lastEmailAttemptAt', 'emailStatus', 'emailError'] as $optionalColumn) {
        if (isset($logColumns[$optionalColumn])) {
            $select[] = 'l.' . $optionalColumn;
        }
    }

    $stmt = $db->query('
        SELECT ' . implode(', ', $select) . '
        FROM account_restore_logs l
        LEFT JOIN utilisateur u ON u.id = l.idUtilisateur
        ORDER BY l.createdAt DESC, l.idRestore DESC
    ');

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function accountRestoreLogsReady(): bool
{
    $db = config::getConnexion();
    return $this->restoreLogColumns($db) !== [];
}

public function accountEmailLogsReady(): bool
{
    $db = config::getConnexion();
    return $this->accountEmailLogColumns($db) !== [];
}

private function fetchAccountEmailLogById(PDO $db, int $emailLogId): ?array
{
    if ($emailLogId <= 0 || $this->accountEmailLogColumns($db) === []) {
        return null;
    }

    $stmt = $db->prepare("SELECT * FROM account_email_logs WHERE idEmailLog = :id LIMIT 1");
    $stmt->execute([':id' => $emailLogId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

public function getAccountEmailLogs(): array
{
    $db = config::getConnexion();
    $logColumns = $this->accountEmailLogColumns($db);
    if ($logColumns === []) {
        return [];
    }

    $select = [
        'l.idEmailLog',
        'l.idUtilisateur',
        'l.email',
        'l.eventType',
        'l.actorId',
        'l.actorRole',
        'l.reason',
        'l.emailStatus',
        'l.emailAttempts',
        'l.lastEmailAttemptAt',
        'l.emailSentAt',
        'l.emailError',
        'l.createdAt',
        'u.nom',
        'u.role',
    ];

    $stmt = $db->query('
        SELECT ' . implode(', ', $select) . '
        FROM account_email_logs l
        LEFT JOIN utilisateur u ON u.id = l.idUtilisateur
        ORDER BY l.createdAt DESC, l.idEmailLog DESC
    ');

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function retryAccountStatusEmailByLogId($emailLogId, $actorId = null, $actorRole = null): array
{
    $db = config::getConnexion();
    $emailLogId = (int)$emailLogId;
    $actorId = $this->resolveActorId($actorId);
    $actorRole = $this->resolveActorRole($actorRole);

    if ($emailLogId <= 0 || $actorId <= 0 || $actorRole === '') {
        return ['success' => false, 'message' => 'Missing actor or email log.'];
    }

    $beforeLog = $this->fetchAccountEmailLogById($db, $emailLogId);
    if (!$beforeLog) {
        return ['success' => false, 'message' => 'Account email log was not found.'];
    }

    $eventType = strtolower(trim((string)($beforeLog['eventType'] ?? '')));
    if (!in_array($eventType, $this->accountEmailEventTypes(), true)) {
        return ['success' => false, 'message' => 'Unsupported email event.'];
    }

    $user = $this->getUserById((int)($beforeLog['idUtilisateur'] ?? 0));
    if (!$user) {
        $user = [
            'id' => (int)($beforeLog['idUtilisateur'] ?? 0),
            'nom' => '',
            'email' => (string)($beforeLog['email'] ?? ''),
            'role' => '',
        ];
    }
    if (!empty($beforeLog['email'])) {
        $user['email'] = (string)$beforeLog['email'];
    }

    $emailResult = $this->sendAccountStatusEmail($user, $eventType, (string)($beforeLog['reason'] ?? ''));
    $this->updateAccountEmailLogAttempt($db, $emailLogId, $emailResult);
    $afterLog = $this->fetchAccountEmailLogById($db, $emailLogId);

    cc_log_admin_entity_action(
        $db,
        $actorId,
        $actorRole,
        'retry_account_status_email',
        'account_email_logs',
        $emailLogId,
        $beforeLog,
        $afterLog,
        'Retry account status email',
        0,
        'not_available'
    );

    return [
        'success' => !empty($emailResult['success']),
        'message' => !empty($emailResult['success']) ? 'Account email sent successfully.' : 'Account email retry failed.',
        'email' => $emailResult,
    ];
}

public function retryRestoreEmailByLogId($restoreLogId, $actorId = null, $actorRole = null): array
{
    $db = config::getConnexion();
    $restoreLogId = (int)$restoreLogId;
    $actorId = $this->resolveActorId($actorId);
    $actorRole = $this->resolveActorRole($actorRole);

    if ($restoreLogId <= 0 || $actorId <= 0 || $actorRole === '') {
        return ['success' => false, 'message' => 'Missing actor or restore log.'];
    }

    $beforeLog = $this->fetchAccountRestoreLogById($db, $restoreLogId);
    if (!$beforeLog) {
        return ['success' => false, 'message' => 'Restore log was not found.'];
    }

    $user = $this->getUserById((int)($beforeLog['idUtilisateur'] ?? 0));
    if (!$user) {
        return ['success' => false, 'message' => 'Restored user was not found.'];
    }

    $emailResult = $this->sendAccountRestoreEmail($user);
    $this->updateRestoreLogEmailAttempt($db, $restoreLogId, $emailResult);
    $afterLog = $this->fetchAccountRestoreLogById($db, $restoreLogId);

    cc_log_admin_entity_action(
        $db,
        $actorId,
        $actorRole,
        'retry_restore_email',
        'account_restore_logs',
        $restoreLogId,
        $beforeLog,
        $afterLog,
        'Retry account restore email',
        0,
        'not_available'
    );

    return [
        'success' => !empty($emailResult['success']),
        'message' => !empty($emailResult['success']) ? 'Restore email sent successfully.' : 'Restore email retry failed.',
        'email' => $emailResult,
    ];
}

public function ajouterAdminAccount($nom, $email, $password, $role) {
    $db = config::getConnexion();

    $check = $db->prepare("SELECT id FROM utilisateur WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        return "Email deja utilise";
    }

    $stmt = $db->prepare("
        INSERT INTO utilisateur (nom, email, mot_de_passe, role, statut, tentatives_login, face_descriptor)
        VALUES (?, ?, ?, ?, 'actif', 0, '')
    ");
    $stmt->execute([
        $nom,
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        $role,
    ]);

    return "success";
}

public function updateUserStatus($id, $status) {
    $db = config::getConnexion();
    $stmt = $db->prepare("UPDATE utilisateur SET statut = ? WHERE id = ?");
    $stmt->execute([$status, (int)$id]);
}

public function suspendUserWithMetadata($targetId, $actorId, $actorRole, $reason): array {
    $db = config::getConnexion();
    $targetId = (int)$targetId;
    $actorId = $this->resolveActorId($actorId);
    $actorRole = $this->resolveActorRole($actorRole);
    $reason = trim((string)$reason);
    $before = $this->getUserById($targetId);

    $stmt = $db->prepare("
        UPDATE utilisateur
        SET statut = 'suspendu',
            suspended_by = ?,
            suspended_by_role = ?,
            suspended_at = NOW(),
            suspension_reason = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $actorId,
        strtolower($actorRole),
        $reason,
        $targetId,
    ]);

    $after = $this->getUserById($targetId);
    $emailLog = $after ? $this->logAccountStatusEmailAttempt($db, $after, 'suspended', $actorId, $actorRole, $reason) : ['log_id' => 0, 'email' => ['success' => false, 'error' => 'User not found after suspension.']];

    return [
        'success' => $stmt->rowCount() >= 0,
        'before' => $before,
        'after' => $after,
        'email_log_id' => $emailLog['log_id'] ?? 0,
        'email' => $emailLog['email'] ?? null,
    ];
}

public function reactivateUserAndClearSuspension($targetId, $actorId = null, $actorRole = null, $reason = null): array {
    $db = config::getConnexion();
    $targetId = (int)$targetId;
    $actorId = $this->resolveActorId($actorId);
    $actorRole = $this->resolveActorRole($actorRole);
    $reason = trim((string)($reason ?? 'Reactivated from BackOffice user management'));
    $before = $this->getUserById($targetId);

    $stmt = $db->prepare("
        UPDATE utilisateur
        SET statut = 'actif',
            suspended_by = NULL,
            suspended_by_role = NULL,
            suspended_at = NULL,
            suspension_reason = NULL
        WHERE id = ?
    ");
    $stmt->execute([$targetId]);

    $after = $this->getUserById($targetId);
    $emailLog = $after ? $this->logAccountStatusEmailAttempt($db, $after, 'reactivated', $actorId, $actorRole, $reason) : ['log_id' => 0, 'email' => ['success' => false, 'error' => 'User not found after reactivation.']];

    return [
        'success' => $stmt->rowCount() >= 0,
        'before' => $before,
        'after' => $after,
        'email_log_id' => $emailLog['log_id'] ?? 0,
        'email' => $emailLog['email'] ?? null,
    ];
}

public function softDeleteUserById($id, $actorId = null, $actorRole = null, $reason = null): array
{
    $db = config::getConnexion();
    if (!$this->userSoftDeleteColumnsReady()) {
        return ['success' => false, 'message' => 'Soft-delete columns are missing. Run the required SQL first.'];
    }

    $targetId = (int)$id;
    $actorId = $this->resolveActorId($actorId);
    $actorRole = $this->resolveActorRole($actorRole);
    $reason = trim((string)($reason ?? 'Deleted from BackOffice user management'));

    if ($targetId <= 0 || $actorId <= 0 || $actorRole === '') {
        return ['success' => false, 'message' => 'Missing actor or target user.'];
    }

    $before = $this->getUserById($targetId);
    if (!$before) {
        return ['success' => false, 'message' => 'Target account was not found.'];
    }
    if (!empty($before['deleted_at'])) {
        return ['success' => false, 'message' => 'Target account is already deleted.'];
    }

    $stmt = $db->prepare("
        UPDATE utilisateur
        SET deleted_at = NOW(),
            deleted_by = :deleted_by,
            deleted_by_role = :deleted_by_role,
            delete_reason = :delete_reason
        WHERE id = :id
          AND deleted_at IS NULL
    ");
    $ok = $stmt->execute([
        ':deleted_by' => $actorId,
        ':deleted_by_role' => $actorRole,
        ':delete_reason' => $reason !== '' ? $reason : null,
        ':id' => $targetId,
    ]);

    if (!$ok || $stmt->rowCount() < 1) {
        return ['success' => false, 'message' => 'Unable to delete this account.'];
    }

    $after = $this->getUserById($targetId);
    cc_log_admin_entity_action($db, $actorId, $actorRole, 'soft_delete_user', 'utilisateur', $targetId, $before, $after, $reason, 1, 'available');
    $emailLog = $after ? $this->logAccountStatusEmailAttempt($db, $after, 'deleted', $actorId, $actorRole, $reason) : ['log_id' => 0, 'email' => null];

    return ['success' => true, 'message' => 'Account deleted successfully.', 'before' => $before, 'after' => $after, 'email_log_id' => $emailLog['log_id'] ?? 0, 'email' => $emailLog['email'] ?? null];
}

public function restoreDeletedUserById($id, $actorId = null, $actorRole = null, $reason = null): array
{
    $db = config::getConnexion();
    if (!$this->userSoftDeleteColumnsReady()) {
        return ['success' => false, 'message' => 'Soft-delete columns are missing. Run the required SQL first.'];
    }

    $targetId = (int)$id;
    $actorId = $this->resolveActorId($actorId);
    $actorRole = $this->resolveActorRole($actorRole);
    $reason = trim((string)($reason ?? 'Restored by Hyper Admin'));

    if ($targetId <= 0 || $actorId <= 0 || $actorRole === '') {
        return ['success' => false, 'message' => 'Missing actor or target user.'];
    }

    $before = $this->getUserById($targetId);
    if (!$before || empty($before['deleted_at'])) {
        return ['success' => false, 'message' => 'Deleted account was not found.'];
    }

    $stmt = $db->prepare("
        UPDATE utilisateur
        SET deleted_at = NULL,
            deleted_by = NULL,
            deleted_by_role = NULL,
            delete_reason = NULL,
            restored_at = NOW(),
            restored_by = :restored_by,
            restored_by_role = :restored_by_role
        WHERE id = :id
          AND deleted_at IS NOT NULL
    ");
    $ok = $stmt->execute([
        ':restored_by' => $actorId,
        ':restored_by_role' => $actorRole,
        ':id' => $targetId,
    ]);

    if (!$ok || $stmt->rowCount() < 1) {
        return ['success' => false, 'message' => 'Unable to restore this account.'];
    }

    $after = $this->getUserById($targetId);
    cc_log_admin_entity_action($db, $actorId, $actorRole, 'restore_deleted_user', 'utilisateur', $targetId, $before, $after, $reason, 0, 'not_available');

    $notificationId = $this->createRestoreNotification($db, $after ?: [], $actorId, $actorRole);
    $emailResult = $this->sendAccountRestoreEmail($after ?: []);
    $restoreLogId = $this->createAccountRestoreLog($db, $before, $after ?: [], $actorId, $actorRole, $reason, $notificationId, $emailResult);
    $accountEmailLogId = $after ? $this->createAccountEmailLog($db, $after, 'restored', $actorId, $actorRole, $reason, $emailResult) : 0;

    return [
        'success' => true,
        'message' => !empty($emailResult['success']) ? 'Account restored successfully. Restore email sent.' : 'Account restored successfully. Restore email failed and can be retried from Restore Logs.',
        'before' => $before,
        'after' => $after,
        'restore_log_id' => $restoreLogId,
        'account_email_log_id' => $accountEmailLogId,
        'notification_id' => $notificationId,
        'email' => $emailResult,
    ];
}

public function finalDeleteUserById($id, $actorId = null, $actorRole = null): array
{
    $db = config::getConnexion();
    if (!$this->userSoftDeleteColumnsReady()) {
        return ['success' => false, 'message' => 'Soft-delete columns are missing. Run the required SQL first.'];
    }

    $targetId = (int)$id;
    $actorId = $this->resolveActorId($actorId);
    $actorRole = $this->resolveActorRole($actorRole);
    if ($targetId <= 0 || $actorId <= 0 || $actorRole === '') {
        return ['success' => false, 'message' => 'Missing actor or target user.'];
    }

    $before = $this->getUserById($targetId);
    if (!$before || empty($before['deleted_at'])) {
        return ['success' => false, 'message' => 'Only soft-deleted accounts can be permanently deleted.'];
    }

    $ageStmt = $db->prepare("SELECT deleted_at <= (NOW() - INTERVAL 7 DAY) AS can_delete FROM utilisateur WHERE id = :id AND deleted_at IS NOT NULL");
    $ageStmt->execute([':id' => $targetId]);
    $canDelete = (int)($ageStmt->fetchColumn() ?: 0) === 1;
    if (!$canDelete) {
        return ['success' => false, 'message' => 'Final delete is available only after 7 days.'];
    }

    cc_log_admin_entity_action($db, $actorId, $actorRole, 'final_delete_user', 'utilisateur', $targetId, $before, null, 'Permanent delete after 7-day soft-delete window', 0, 'not_available');

    $stmt = $db->prepare("DELETE FROM utilisateur WHERE id = :id AND deleted_at IS NOT NULL AND deleted_at <= (NOW() - INTERVAL 7 DAY)");
    $ok = $stmt->execute([':id' => $targetId]);
    if (!$ok || $stmt->rowCount() < 1) {
        return ['success' => false, 'message' => 'Unable to permanently delete this account.'];
    }

    return ['success' => true, 'message' => 'Account permanently deleted.'];
}

public function deleteUserById($id) {
    $result = $this->softDeleteUserById($id, null, null, 'Deleted from Admin Management');
    return (bool)($result['success'] ?? false);
}

    public function afficherUsers($search = '', $role = '', $page = 1, $limit = 10) {
    $db = config::getConnexion();
    $sql = "SELECT * FROM utilisateur WHERE 1=1" . $this->activeUserFilter($db);
    
    if (!empty($search)) {
        $sql .= " AND (nom LIKE :search OR email LIKE :search)";
    }
    
    if (!empty($role)) {
        $sql .= " AND role = :role";
    }
    
    $sql .= " ORDER BY id DESC";

    $stmt = $db->prepare($sql);
    
    if (!empty($search)) {
        $searchTerm = "%$search%";
        $stmt->bindParam(':search', $searchTerm);
    }
    
    if (!empty($role)) {
        $stmt->bindParam(':role', $role);
    }
    
    // Backward compatibility: if only search and role are passed, return raw result array.
    if (func_num_args() < 3) {
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get total count for pagination
    $countSql = str_replace("SELECT *", "SELECT COUNT(*)", $sql);
    $countStmt = $db->prepare($countSql);
    
    if (!empty($search)) {
        $countStmt->bindParam(':search', $searchTerm);
    }
    
    if (!empty($role)) {
        $countStmt->bindParam(':role', $role);
    }
    
    $countStmt->execute();
    $totalRecords = $countStmt->fetchColumn();
    
    // Add LIMIT and OFFSET
    $offset = ($page - 1) * $limit;
    $sql .= " LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    
    if (!empty($search)) {
        $stmt->bindParam(':search', $searchTerm);
    }
    
    if (!empty($role)) {
        $stmt->bindParam(':role', $role);
    }
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'data' => $results,
        'total' => $totalRecords,
        'page' => $page,
        'limit' => $limit,
        'totalPages' => ceil($totalRecords / $limit)
    ];
}

public function sendResetLink($email) {
    $db = config::getConnexion();

    $query = $db->prepare("SELECT * FROM utilisateur WHERE email=?" . $this->activeUserFilter($db));
    $query->execute([$email]);
    $user = $query->fetch();

    if (!$user) return "Email introuvable";

    // 🔐 Token sécurisé
    $token = bin2hex(random_bytes(50));
    $expire = date("Y-m-d H:i:s", strtotime("+15 minutes"));

    // Sauvegarde en DB
    $sql = "UPDATE utilisateur SET reset_token=?, reset_expire=? WHERE email=?";
    $db->prepare($sql)->execute([$token, $expire, $email]);

    $baseUrl = $this->buildPasswordResetBaseUrl();
    $link = rtrim($baseUrl, '/') . '/Vue/FrontOffice/utilisateur/reset_password.php?token=' . urlencode($token);

    $mail = new PHPMailer(true);

    try {
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'neylamhamddy@gmail.com';
        $mail->Password = 'aebg mpbl zomq idjn'; // ⚠️ PAS ton vrai mdp
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('neylamhamddy@gmail.com', 'Crea8Connect');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Réinitialisation du mot de passe - Crea8Connect';
       $mail->Body = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body {
        margin:0;
        padding:0;
        background:#f4f6f9;
        font-family: Arial, sans-serif;
    }
    .container {
        max-width:600px;
        margin:40px auto;
        background:#ffffff;
        border-radius:12px;
        overflow:hidden;
        box-shadow:0 5px 15px rgba(0,0,0,0.1);
    }
    .header {
        background:linear-gradient(135deg, #9B5DE0, #B771E5);
        color:white;
        text-align:center;
        padding:30px;
        font-size:22px;
        font-weight:bold;
    }
    .content {
        padding:30px;
        color:#333;
        text-align:center;
    }
    .btn {
        display:inline-block;
        margin-top:20px;
        padding:12px 25px;
        background:#9B5DE0;
        color:white !important;
        text-decoration:none;
        border-radius:6px;
        font-weight:bold;
    }
    .footer {
        text-align:center;
        font-size:12px;
        color:#999;
        padding:20px;
    }
</style>
</head>

<body>

<div class="container">

    <div class="header">
        🔐 Crea8Connect
    </div>

    <div class="content">
        <h2>Mot de passe oublié ?</h2>

        <p>Pas de panique 👌<br>
        Clique sur le bouton ci-dessous pour réinitialiser ton mot de passe.</p>

        <a href="'.$link.'" class="btn">Réinitialiser le mot de passe</a>

        <p style="margin-top:25px; font-size:13px; color:#666;">
            ⏱ Ce lien expire dans <strong>15 minutes</strong>
        </p>
    </div>

    <div class="footer">
        © '.date("Y").' Crea8Connect — Tous droits réservés
    </div>

</div>

</body>
</html>
';
        $mail->send();

        return "Email envoyé avec succès";
    } catch (Exception $e) {
        return "Erreur: " . $mail->ErrorInfo;
    }
}
public function resetPassword($password, $token) {
    $db = config::getConnexion();

    $sql = "SELECT * FROM utilisateur 
            WHERE reset_token=? AND reset_expire > NOW()" . $this->activeUserFilter($db);
    $query = $db->prepare($sql);
    $query->execute([$token]);

    $user = $query->fetch();

    if (!$user) return "Lien invalide ou expiré";

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $sql = "UPDATE utilisateur 
            SET mot_de_passe=?, reset_token=NULL, reset_expire=NULL 
            WHERE id=?";
    $db->prepare($sql)->execute([$hashedPassword, $user['id']]);

    return "Mot de passe mis à jour";
}
    public function getStatistiquesUtilisateurs() {
        $db = config::getConnexion();
        
        // Total utilisateurs
        $activeFilter = $this->activeUserFilter($db);
        $total = $db->query("SELECT COUNT(*) as total FROM utilisateur WHERE 1=1$activeFilter")->fetch()['total'];
        
        // Par rôle
        $admin = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE role IN ('admin', 'super_admin', 'hyper_admin')$activeFilter")->fetch()['count'];
        $createur = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE role='createur'$activeFilter")->fetch()['count'];
        $marque = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE role='marque'$activeFilter")->fetch()['count'];
        
        // Par statut - Inclure les NULL (traiter comme 'actif' par défaut)
        $actif = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE (statut='actif' OR statut IS NULL)$activeFilter")->fetch()['count'];
        $inactif = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE statut='inactif'$activeFilter")->fetch()['count'];
        $suspendu = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE statut='suspendu'$activeFilter")->fetch()['count'];
        $en_attente = $db->query("SELECT COUNT(*) as count FROM utilisateur WHERE statut='en_attente'$activeFilter")->fetch()['count'];
        
        return [
            'total' => $total,
            'admin' => $admin,
            'createur' => $createur,
            'marque' => $marque,
            'actif' => $actif,
            'inactif' => $inactif,
            'suspendu' => $suspendu,
            'en_attente' => $en_attente
        ];
    }
    public function sendReclamationResponseNotification($email, $nom, $description, $reponse) {
        $mail = new PHPMailer(true);

        try {
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'neylamhamddy@gmail.com';
            $mail->Password = 'aebg mpbl zomq idjn';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('neylamhamddy@gmail.com', 'Crea8Connect');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Réponse à votre réclamation - Crea8Connect';

            $mail->Body = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body {
        margin:0;
        padding:0;
        background:#f4f6f9;
        font-family: Arial, sans-serif;
    }
    .container {
        max-width:600px;
        margin:40px auto;
        background:#ffffff;
        border-radius:12px;
        overflow:hidden;
        box-shadow:0 5px 15px rgba(0,0,0,0.1);
    }
    .header {
        background:linear-gradient(135deg, #9B5DE0, #B771E5);
        color:white;
        text-align:center;
        padding:30px 20px;
    }
    .content {
        padding:30px 20px;
        line-height:1.6;
        color:#333;
    }
    .reclamation {
        background:#f8f9fa;
        border-left:4px solid #9B5DE0;
        padding:15px;
        margin:20px 0;
        border-radius:4px;
    }
    .response {
        background:#e8f4f8;
        border-left:4px solid #28a745;
        padding:15px;
        margin:20px 0;
        border-radius:4px;
    }
    .footer {
        background:#f8f9fa;
        text-align:center;
        padding:20px;
        color:#666;
        font-size:14px;
    }
</style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔔 Réponse à votre réclamation</h1>
        </div>
        <div class="content">
            <p>Bonjour <strong>' . htmlspecialchars($nom) . '</strong>,</p>

            <p>Votre réclamation a reçu une réponse de notre équipe :</p>

            <div class="reclamation">
                <h4>📝 Votre réclamation :</h4>
                <p>' . nl2br(htmlspecialchars($description)) . '</p>
            </div>

            <div class="response">
                <h4>💬 Réponse de l\'équipe :</h4>
                <p>' . nl2br(htmlspecialchars($reponse)) . '</p>
            </div>

            <p>Si vous avez d\'autres questions, n\'hésitez pas à nous contacter.</p>

            <p>Cordialement,<br>
            <strong>L\'équipe Crea8Connect</strong></p>
        </div>
        <div class="footer">
            <p>© 2024 Crea8Connect - Tous droits réservés</p>
        </div>
    </div>
</body>
</html>';

            $mail->send();
            return true;

        } catch (Exception $e) {
            // Log l'erreur pour le debugging
            error_log("Erreur envoi email réclamation: " . $mail->ErrorInfo);
            return false;
        }
    }
    public function supprimerUser($id) {
        $result = $this->softDeleteUserById($id, null, null, 'Deleted from BackOffice user management');
        return (bool)($result['success'] ?? false);
    }


private function getProjectBaseUrl(): string {
    $script = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');

    foreach (['/Vue/FrontOffice/', '/Vue/BackOffice/'] as $marker) {
        $pos = strpos($script, $marker);
        if ($pos !== false) {
            return substr($script, 0, $pos);
        }
    }

    return '';
}

private function appUrl(string $path): string {
    return rtrim($this->getProjectBaseUrl(), '/') . '/' . ltrim($path, '/');
}

private function normalizeRole($role): string {
    $role = strtolower(trim((string) $role));
    $role = str_replace(
        ['é', 'è', 'ê', 'ë', 'à', 'â', 'î', 'ï', 'ô', 'ù', 'û', 'ç'],
        ['e', 'e', 'e', 'e', 'a', 'a', 'i', 'i', 'o', 'u', 'u', 'c'],
        $role
    );

    return match ($role) {
        'brand' => 'marque',
        'creator', 'creatrice' => 'createur',
        'administrator', 'administrateur' => 'admin',
        default => $role,
    };
}

public function login($email, $password) {

    $db = config::getConnexion();

    $email = trim($email);
    $password = trim($password);

    $query = $db->prepare("
        SELECT * 
        FROM utilisateur 
        WHERE email=?
    ");

    $query->execute([$email]);

    $user = $query->fetch();

    if (!$user) {
        return "Utilisateur introuvable";
    }

    if (!empty($user['deleted_at'] ?? null)) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        cc_clear_normal_auth_session(false);
        cc_set_account_appeal_session($user, 'account_deleted');
        session_write_close();
        header('Location: ' . $this->appUrl('Vue/FrontOffice/utilisateur/reclamation.php?appeal=1&reason=account_deleted'));
        exit();
    }

    // TEST PASSWORD — handle both bcrypt hashes and legacy plain text
    $passwordOk = password_verify($password, $user['mot_de_passe'])
               || $password === $user['mot_de_passe'];

    if (!$passwordOk) {
        return "Mot de passe incorrect";
    }

    // If password was plain text, upgrade it to a hash now
    if ($password === $user['mot_de_passe']) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $db->prepare("UPDATE utilisateur SET mot_de_passe = ? WHERE id = ?")
           ->execute([$hashed, $user['id']]);
    }

    // SESSION
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $normalizedRole = $this->normalizeRole($user['role'] ?? '');
    $user['role'] = $normalizedRole;

    if ($user['statut'] === 'suspendu') {
        cc_clear_normal_auth_session(false);
        cc_set_account_appeal_session(array_merge($user, ['role' => $normalizedRole]), 'account_suspended');

        session_write_close();
        header('Location: ' . $this->appUrl('Vue/FrontOffice/utilisateur/reclamation.php?appeal=1&reason=account_suspended'));
        exit();
    }

    if ($user['statut'] != 'actif') {
        return "Compte non actif";
    }

    unset($_SESSION['suspended_appeal']);
    $_SESSION['connected'] = true;
    $_SESSION['id'] = $user['id'];
    $_SESSION['nom'] = $user['nom'];
    $_SESSION['email'] = $user['email'] ?? '';
    $_SESSION['role'] = $normalizedRole;
    $_SESSION['user'] = $user;

    // Keep the real utilisateur login as the source of truth and clear old offre demo data.
    unset($_SESSION['utilisateur']);
    $_SESSION['utilisateur'] = [
        'id' => (int) $user['id'],
        'role' => $normalizedRole,
        'nom' => $user['nom'] ?? '',
        'email' => $user['email'] ?? '',
    ];

    session_write_close();

    // REDIRECTION
    if (isBackOfficeRole($normalizedRole)) {
        header('Location: ' . $this->appUrl('Vue/BackOffice/dashboard/index.php'));
    } else {
        header('Location: ' . $this->appUrl('Vue/FrontOffice/utilisateur/creator.php'));
    }

    exit();
}
}
?>
