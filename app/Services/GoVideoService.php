<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * GoVideoService - Integration with Go Video Streaming Server
 * 
 * This service handles communication between Laravel and the high-performance
 * Go video streaming server for optimal video delivery.
 */
class GoVideoService
{
    protected string $serverUrl;
    protected string $secretKey;
    protected int $urlExpiry;

    public function __construct()
    {
        $this->serverUrl = config('playtube.go_video_server_url', 'http://localhost:8090');
        $this->secretKey = config('playtube.go_video_secret_key', 'playtube-video-secret-key-change-in-production');
        $this->urlExpiry = config('playtube.signed_url_expiry', 3600); // 1 hour default
    }

    /**
     * Check if Go Video Server is healthy
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(2)->get("{$this->serverUrl}/health");
            return $response->ok() && $response->json('status') === 'healthy';
        } catch (\Exception $e) {
            Log::warning('Go Video Server health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get streaming URL for a video (with optional signature for production)
     */
    public function getStreamUrl(Video $video, ?string $quality = null): string
    {
        $uuid = $video->uuid;
        $path = $quality ? "/stream/{$uuid}/{$quality}" : "/stream/{$uuid}";

        if (app()->environment('production')) {
            return $this->signUrl($path);
        }

        return "{$this->serverUrl}{$path}";
    }

    /**
     * Get HLS Master Playlist URL
     */
    public function getHlsUrl(Video $video): string
    {
        $path = "/hls/{$video->uuid}/master.m3u8";

        if (app()->environment('production')) {
            return $this->signUrl($path);
        }

        return "{$this->serverUrl}{$path}";
    }

    /**
     * Get HLS URL for specific quality
     */
    public function getHlsQualityUrl(Video $video, string $quality): string
    {
        $path = "/hls/{$video->uuid}/{$quality}/playlist.m3u8";

        if (app()->environment('production')) {
            return $this->signUrl($path);
        }

        return "{$this->serverUrl}{$path}";
    }

    /**
     * Get DASH Manifest URL
     */
    public function getDashUrl(Video $video): string
    {
        $path = "/dash/{$video->uuid}/manifest.mpd";

        if (app()->environment('production')) {
            return $this->signUrl($path);
        }

        return "{$this->serverUrl}{$path}";
    }

    /**
     * Get thumbnail URL from Go server
     */
    public function getThumbnailUrl(Video $video): string
    {
        return "{$this->serverUrl}/thumb/{$video->uuid}";
    }

    /**
     * Get all available streaming URLs for a video
     */
    public function getStreamingUrls(Video $video): array
    {
        $urls = [
            'progressive' => $this->getStreamUrl($video),
            'hls' => $this->getHlsUrl($video),
            'dash' => $this->getDashUrl($video),
            'thumbnail' => $this->getThumbnailUrl($video),
            'qualities' => [],
        ];

        // Add quality-specific URLs
        $renditions = $video->renditions ?? [];
        $availableQualities = ['360p', '480p', '720p', '1080p'];

        foreach ($availableQualities as $quality) {
            if (isset($renditions[$quality]) || $this->qualityExists($video, $quality)) {
                $urls['qualities'][$quality] = [
                    'progressive' => $this->getStreamUrl($video, $quality),
                    'hls' => $this->getHlsQualityUrl($video, $quality),
                ];
            }
        }

        return $urls;
    }

    /**
     * Get server statistics
     */
    public function getStats(): ?array
    {
        try {
            $response = Http::timeout(5)->get("{$this->serverUrl}/stats");
            if ($response->ok()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get Go Video Server stats', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Generate signed URL for production
     */
    protected function signUrl(string $path): string
    {
        $expires = time() + $this->urlExpiry;
        $uuid = $this->extractUuidFromPath($path);
        $signature = $this->generateSignature($uuid, $expires);

        $separator = str_contains($path, '?') ? '&' : '?';
        return "{$this->serverUrl}{$path}{$separator}expires={$expires}&sig={$signature}";
    }

    /**
     * Generate HMAC signature
     */
    protected function generateSignature(string $uuid, int $expires): string
    {
        $data = "{$uuid}:{$expires}";
        return hash_hmac('sha256', $data, $this->secretKey);
    }

    /**
     * Extract UUID from path
     */
    protected function extractUuidFromPath(string $path): string
    {
        preg_match('/\/(?:stream|hls|dash)\/([a-f0-9\-]+)/i', $path, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Check if a quality version exists for video
     */
    protected function qualityExists(Video $video, string $quality): bool
    {
        $cacheKey = "video_quality_{$video->uuid}_{$quality}";
        
        return Cache::remember($cacheKey, 300, function () use ($video, $quality) {
            $basePath = storage_path("app/private/videos/{$video->uuid}");
            
            $possiblePaths = [
                "{$basePath}/{$quality}.mp4",
                "{$basePath}/renditions/{$quality}.mp4",
            ];

            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Determine if Go server should be used
     */
    public function shouldUseGoServer(): bool
    {
        // Use Go server if enabled in config and healthy
        if (!config('playtube.use_go_video_server', true)) {
            return false;
        }

        // Cache health status for 30 seconds to reduce health check spam
        return Cache::remember('go_video_server_healthy', 30, function () {
            $healthy = $this->isHealthy();
            
            // Log only on state change (once per cache period)
            if (!$healthy) {
                Log::warning('Go Video Server is not reachable, will fallback to direct streaming', [
                    'server_url' => $this->serverUrl,
                ]);
            }
            
            return $healthy;
        });
    }

    /**
     * Get fallback URL when Go server is unavailable
     * Returns direct storage URL for PHP/Nginx streaming
     */
    public function getFallbackStreamUrl(Video $video): ?string
    {
        // Try stream.mp4 first (optimized for streaming)
        if ($video->stream_path) {
            return asset('storage/' . $video->stream_path);
        }
        
        // Fallback to original file
        if ($video->original_path) {
            return asset('storage/' . $video->original_path);
        }
        
        return null;
    }

    /**
     * Get stream URL with automatic fallback
     * If Go server is unavailable, falls back to direct file URL
     */
    public function getStreamUrlWithFallback(Video $video, ?string $quality = null): string
    {
        if ($this->shouldUseGoServer()) {
            return $this->getStreamUrl($video, $quality);
        }
        
        // Fallback to direct streaming
        $fallback = $this->getFallbackStreamUrl($video);
        if ($fallback) {
            return $fallback;
        }
        
        // Last resort: return Go server URL anyway (may fail but shows error)
        return $this->getStreamUrl($video, $quality);
    }

    /**
     * Get the best delivery URL based on client capabilities
     */
    public function getBestStreamUrl(Video $video, array $clientCapabilities = []): array
    {
        $supportsHls = $clientCapabilities['hls'] ?? true;
        $supportsDash = $clientCapabilities['dash'] ?? false;
        $preferredQuality = $clientCapabilities['quality'] ?? 'auto';

        $response = [
            'type' => 'progressive',
            'url' => $this->getStreamUrl($video),
            'qualities' => [],
        ];

        // Prefer HLS for adaptive streaming
        if ($supportsHls) {
            $response['type'] = 'hls';
            $response['url'] = $this->getHlsUrl($video);
        } elseif ($supportsDash) {
            $response['type'] = 'dash';
            $response['url'] = $this->getDashUrl($video);
        }

        // Add quality options
        $urls = $this->getStreamingUrls($video);
        $response['qualities'] = $urls['qualities'];
        $response['thumbnail'] = $urls['thumbnail'];

        return $response;
    }
}
