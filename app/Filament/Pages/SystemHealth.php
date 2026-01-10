<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class SystemHealth extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-server-stack';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 100;
    protected static string $view = 'filament.pages.system-health';

    public function getTitle(): string|Htmlable
    {
        return 'System Health';
    }

    public static function getNavigationLabel(): string
    {
        return 'System Health';
    }

    /**
     * Parse PHP size string (e.g., "128M", "2G") to bytes
     */
    protected function parsePhpSize(string $size): int
    {
        $size = trim($size);
        $unit = strtoupper(substr($size, -1));
        $value = (int) $size;
        
        return match ($unit) {
            'G' => $value * 1024 * 1024 * 1024,
            'M' => $value * 1024 * 1024,
            'K' => $value * 1024,
            default => $value,
        };
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / 1024 / 1024 / 1024, 2) . ' GB';
        }
        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    /**
     * Get upload health data for the view
     */
    public function getUploadHealth(): array
    {
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');
        $maxExecutionTime = ini_get('max_execution_time');
        $memoryLimit = ini_get('memory_limit');
        
        // Site Setting value
        $configuredMaxMb = (int) Setting::get('max_upload_size', 2048);
        $configuredMaxBytes = $configuredMaxMb * 1024 * 1024;
        
        // PHP limits
        $phpUploadBytes = $this->parsePhpSize($uploadMaxFilesize);
        $phpPostBytes = $this->parsePhpSize($postMaxSize);
        $phpMinBytes = min($phpUploadBytes, $phpPostBytes);
        
        // Effective limit (lowest of all)
        $effectiveLimit = min($configuredMaxBytes, $phpMinBytes);
        
        // Warnings
        $warnings = [];
        $errors = [];
        
        if ($configuredMaxBytes > $phpMinBytes) {
            $errors[] = "Site Setting ({$configuredMaxMb}MB) exceeds PHP limit ({$this->formatBytes($phpMinBytes)}). Users will get cryptic errors.";
        }
        
        if ($phpUploadBytes !== $phpPostBytes) {
            $warnings[] = "upload_max_filesize ({$uploadMaxFilesize}) != post_max_size ({$postMaxSize}). Consider setting them equal.";
        }
        
        if ((int) $maxExecutionTime < 300 && (int) $maxExecutionTime > 0) {
            $warnings[] = "max_execution_time ({$maxExecutionTime}s) may be too low for large uploads.";
        }
        
        // FFmpeg check
        $ffmpegInstalled = $this->checkCommand('ffmpeg');
        $ffprobeInstalled = $this->checkCommand('ffprobe');
        
        if (!$ffmpegInstalled) {
            $errors[] = 'FFmpeg is not installed. Video processing will fail.';
        }
        if (!$ffprobeInstalled) {
            $errors[] = 'FFprobe is not installed. Video metadata extraction will fail.';
        }
        
        // Storage writable check
        $storageWritable = is_writable(storage_path('app/public'));
        if (!$storageWritable) {
            $errors[] = 'Storage directory is not writable. Uploads will fail.';
        }
        
        return [
            'php_limits' => [
                'upload_max_filesize' => $uploadMaxFilesize,
                'upload_max_filesize_bytes' => $phpUploadBytes,
                'post_max_size' => $postMaxSize,
                'post_max_size_bytes' => $phpPostBytes,
                'max_execution_time' => $maxExecutionTime ?: 'Unlimited',
                'memory_limit' => $memoryLimit,
            ],
            'site_settings' => [
                'max_upload_size_mb' => $configuredMaxMb,
                'max_upload_size_bytes' => $configuredMaxBytes,
            ],
            'effective_limit' => [
                'bytes' => $effectiveLimit,
                'formatted' => $this->formatBytes($effectiveLimit),
            ],
            'tools' => [
                'ffmpeg' => $ffmpegInstalled,
                'ffprobe' => $ffprobeInstalled,
            ],
            'storage' => [
                'writable' => $storageWritable,
                'path' => storage_path('app/public'),
            ],
            'warnings' => $warnings,
            'errors' => $errors,
            'status' => count($errors) > 0 ? 'error' : (count($warnings) > 0 ? 'warning' : 'ok'),
        ];
    }

    /**
     * Check if a command exists
     */
    protected function checkCommand(string $command): bool
    {
        $result = shell_exec("which {$command} 2>/dev/null");
        return !empty(trim($result ?? ''));
    }

    /**
     * Get queue worker status
     */
    public function getQueueStatus(): array
    {
        $queueConnection = config('queue.default');
        
        // Check if queue workers are running (basic check via process list)
        $workersRunning = false;
        if (function_exists('shell_exec')) {
            $processes = shell_exec('ps aux | grep "[q]ueue:work" 2>/dev/null') ?? '';
            $workersRunning = !empty(trim($processes));
        }
        
        return [
            'connection' => $queueConnection,
            'workers_running' => $workersRunning,
        ];
    }

    /**
     * Get disk space info
     */
    public function getDiskSpace(): array
    {
        $storagePath = storage_path();
        $totalSpace = disk_total_space($storagePath);
        $freeSpace = disk_free_space($storagePath);
        $usedSpace = $totalSpace - $freeSpace;
        $usagePercent = round(($usedSpace / $totalSpace) * 100, 1);
        
        return [
            'total' => $this->formatBytes($totalSpace),
            'free' => $this->formatBytes($freeSpace),
            'used' => $this->formatBytes($usedSpace),
            'usage_percent' => $usagePercent,
            'warning' => $usagePercent > 90,
        ];
    }
}
