<?php

namespace App\Console\Commands\Video;

use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ProbeVideoCommand extends Command
{
    protected $signature = 'video:probe {video : Video ID or UUID}';
    protected $description = 'Probe video file and check if it\'s streamable (faststart)';

    public function handle(): int
    {
        $identifier = $this->argument('video');
        
        // Find video by ID or UUID
        $video = is_numeric($identifier) 
            ? Video::find($identifier) 
            : Video::where('uuid', $identifier)->first();

        if (!$video) {
            $this->error("Video not found: {$identifier}");
            return self::FAILURE;
        }

        $this->info("=== Video Information ===");
        $this->info("ID: {$video->id}");
        $this->info("UUID: {$video->uuid}");
        $this->info("Title: {$video->title}");
        $this->info("Status: {$video->status}");
        $this->newLine();

        $this->info("=== Processing State ===");
        $this->info("Processing State: {$video->processing_state}");
        $this->info("Processing Progress: {$video->processing_progress}%");
        $this->info("Stream Ready: " . ($video->stream_ready ? 'YES' : 'NO'));
        if ($video->processing_error) {
            $this->error("Error: {$video->processing_error}");
        }
        $this->newLine();

        $this->info("=== File Paths ===");
        $this->checkFile('Original', $video->original_path);
        $this->checkFile('Stream', $video->stream_path);
        $this->checkFile('Thumbnail', $video->thumbnail_path);
        $this->newLine();

        if ($video->renditions && is_array($video->renditions)) {
            $this->info("=== Renditions ===");
            foreach ($video->renditions as $quality => $info) {
                $exists = isset($info['path']) && Storage::disk('public')->exists($info['path']);
                $status = $exists ? '✓' : '✗';
                $size = $exists ? ' (' . $this->formatBytes($info['filesize'] ?? 0) . ')' : '';
                $this->line("{$status} {$quality}p: {$info['path']}{$size}");
            }
            $this->newLine();
        }

        // Probe stream file with ffprobe if available
        if ($video->stream_path && Storage::disk('public')->exists($video->stream_path)) {
            $this->info("=== ffprobe Analysis ===");
            $path = Storage::disk('public')->path($video->stream_path);
            $this->probeWithFfprobe($path);
        }

        return self::SUCCESS;
    }

    protected function checkFile(string $label, ?string $path): void
    {
        if (!$path) {
            $this->line("{$label}: <fg=gray>Not set</>");
            return;
        }

        $exists = Storage::disk('public')->exists($path);
        
        if ($exists) {
            $fullPath = Storage::disk('public')->path($path);
            $size = $this->formatBytes(filesize($fullPath));
            $this->line("{$label}: <fg=green>✓</> {$path} ({$size})");
        } else {
            $this->line("{$label}: <fg=red>✗</> {$path} (missing)");
        }
    }

    protected function probeWithFfprobe(string $path): void
    {
        $ffprobe = $this->findExecutable('ffprobe');
        
        if (!$ffprobe) {
            $this->warn('ffprobe not found, skipping analysis');
            return;
        }

        $cmd = sprintf(
            '%s -v quiet -print_format json -show_format -show_streams %s 2>&1',
            escapeshellarg($ffprobe),
            escapeshellarg($path)
        );

        $output = shell_exec($cmd);
        $data = json_decode($output, true);

        if (!$data) {
            $this->error('Failed to probe file');
            return;
        }

        $format = $data['format'] ?? [];
        $videoStream = collect($data['streams'] ?? [])->firstWhere('codec_type', 'video');

        $this->line("Duration: " . gmdate('H:i:s', (int)($format['duration'] ?? 0)));
        $this->line("Size: " . $this->formatBytes($format['size'] ?? 0));
        $this->line("Bitrate: " . round(($format['bit_rate'] ?? 0) / 1000) . " kbps");
        $this->newLine();

        if ($videoStream) {
            $this->line("Video: {$videoStream['codec_name']} {$videoStream['width']}x{$videoStream['height']}");
        }

        $isFaststart = $this->checkFaststart($path);
        if ($isFaststart) {
            $this->info('✓ Faststart: YES - STREAMABLE');
        } else {
            $this->warn('✗ Faststart: NO - NOT STREAMABLE');
        }
    }

    protected function checkFaststart(string $path): bool
    {
        $handle = fopen($path, 'rb');
        if (!$handle) return false;
        $header = fread($handle, 8192);
        fclose($handle);
        return strpos($header, 'moov') !== false;
    }

    protected function findExecutable(string $name): ?string
    {
        $which = trim(shell_exec("which {$name} 2>/dev/null") ?? '');
        return ($which && file_exists($which)) ? $which : null;
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < 3) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
