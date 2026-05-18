<?php
require_once __DIR__ . '/../config.php';

class ServerCenterC
{
    private string $projectRoot;

    public function __construct(?string $projectRoot = null)
    {
        $this->projectRoot = $projectRoot ?: dirname(__DIR__);
    }

    public function getServerSummary(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'os_family' => PHP_OS_FAMILY,
            'os_name' => defined('PHP_OS') ? PHP_OS : 'Unknown',
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unavailable',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unavailable',
            'project_root' => $this->projectRoot,
            'current_time' => date('Y-m-d H:i:s'),
        ];
    }

    public function getDiskSummary($path): array
    {
        $path = $this->safePath($path);
        $total = @disk_total_space($path);
        $free = @disk_free_space($path);

        if ($total === false || $free === false || $total <= 0) {
            return [
                'available' => false,
                'total_bytes' => 0,
                'free_bytes' => 0,
                'used_bytes' => 0,
                'percent_used' => 0,
                'total_human' => 'Unavailable',
                'free_human' => 'Unavailable',
                'used_human' => 'Unavailable',
            ];
        }

        $used = max(0, $total - $free);

        return [
            'available' => true,
            'total_bytes' => (float)$total,
            'free_bytes' => (float)$free,
            'used_bytes' => (float)$used,
            'percent_used' => round(($used / $total) * 100, 1),
            'total_human' => $this->humanBytes($total),
            'free_human' => $this->humanBytes($free),
            'used_human' => $this->humanBytes($used),
        ];
    }

    public function getFolderStatus($path): array
    {
        $path = $this->safePath($path);
        $exists = file_exists($path);
        $isDir = is_dir($path);
        $scan = $isDir ? $this->safeFolderSize($path) : [
            'size_bytes' => 0,
            'file_count' => 0,
            'directory_count' => 0,
            'partial' => false,
            'note' => $exists ? 'Not a directory' : 'Missing',
        ];

        return [
            'path' => $path,
            'label' => $this->folderLabel($path),
            'exists' => $exists,
            'is_dir' => $isDir,
            'is_readable' => $exists && is_readable($path),
            'is_writable' => $exists && is_writable($path),
            'size_bytes' => $scan['size_bytes'],
            'size_human' => $this->humanBytes($scan['size_bytes']),
            'file_count' => $scan['file_count'],
            'directory_count' => $scan['directory_count'],
            'partial' => $scan['partial'],
            'note' => $scan['note'],
        ];
    }

    public function getDatabaseStatus(): array
    {
        try {
            $db = config::getConnexion();
            $stmt = $db->query('SELECT 1');
            $ok = (int)$stmt->fetchColumn() === 1;

            return [
                'connected' => $ok,
                'message' => $ok ? 'Connected' : 'Connection check failed',
            ];
        } catch (Throwable $e) {
            error_log('Server Center DB check failed: ' . $e->getMessage());
            return [
                'connected' => false,
                'message' => 'Not connected',
            ];
        }
    }

    public function getFolderChecks(): array
    {
        $paths = [
            $this->projectRoot,
            $this->projectRoot . DIRECTORY_SEPARATOR . 'storage',
            $this->projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads',
            $this->projectRoot . DIRECTORY_SEPARATOR . 'Vue' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads',
            $this->projectRoot . DIRECTORY_SEPARATOR . 'Vue' . DIRECTORY_SEPARATOR . 'images',
            $this->projectRoot . DIRECTORY_SEPARATOR . 'vendor',
        ];

        $backupCandidates = [
            $this->projectRoot . DIRECTORY_SEPARATOR . 'backup',
            $this->projectRoot . DIRECTORY_SEPARATOR . 'backups',
            $this->projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups',
        ];

        foreach ($backupCandidates as $candidate) {
            if (file_exists($candidate)) {
                $paths[] = $candidate;
            }
        }

        return array_map(fn($path) => $this->getFolderStatus($path), array_values(array_unique($paths)));
    }

    public function humanBytes($bytes): string
    {
        $bytes = (float)$bytes;
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int)floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return round($value, $power === 0 ? 0 : 2) . ' ' . $units[$power];
    }

    public function safeFolderSize($path, $maxFiles = 5000): array
    {
        $path = $this->safePath($path);
        $maxFiles = max(1, (int)$maxFiles);
        $size = 0;
        $files = 0;
        $dirs = 0;
        $partial = false;
        $note = '';

        if (!is_dir($path) || !is_readable($path)) {
            return [
                'size_bytes' => 0,
                'file_count' => 0,
                'directory_count' => 0,
                'partial' => false,
                'note' => is_dir($path) ? 'Not readable' : 'Missing',
            ];
        }

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isLink()) {
                    continue;
                }

                if ($item->isDir()) {
                    $dirs++;
                    continue;
                }

                if ($item->isFile()) {
                    $files++;
                    $size += max(0, (int)$item->getSize());
                    if ($files >= $maxFiles) {
                        $partial = true;
                        $note = 'Scan limit reached';
                        break;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('Server Center folder scan failed: ' . $e->getMessage());
            $note = 'Scan incomplete';
            $partial = true;
        }

        return [
            'size_bytes' => $size,
            'file_count' => $files,
            'directory_count' => $dirs,
            'partial' => $partial,
            'note' => $note,
        ];
    }

    private function safePath($path): string
    {
        return rtrim((string)$path, DIRECTORY_SEPARATOR);
    }

    private function folderLabel(string $path): string
    {
        if ($path === $this->projectRoot) {
            return 'project root';
        }

        return ltrim(str_replace($this->projectRoot, '', $path), DIRECTORY_SEPARATOR) ?: basename($path);
    }
}
