<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Service to parse and process embed URLs from various video platforms.
 * Supports: YouTube, Dailymotion, Google Drive, Vimeo, Facebook, Twitter/X, Streamable, etc.
 */
class EmbedService
{
    /**
     * List of supported platforms with their patterns and processors.
     */
    protected array $platforms = [
        'youtube' => [
            'patterns' => [
                '/(?:youtube\.com\/(?:watch\?v=|embed\/|v\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/',
            ],
            'embed_template' => 'https://www.youtube.com/embed/{id}?rel=0&modestbranding=1',
            'thumbnail_template' => 'https://img.youtube.com/vi/{id}/maxresdefault.jpg',
            'thumbnail_fallback' => 'https://img.youtube.com/vi/{id}/hqdefault.jpg',
        ],
        'dailymotion' => [
            'patterns' => [
                '/dailymotion\.com\/(?:video|embed\/video)\/([a-zA-Z0-9]+)/',
                '/dai\.ly\/([a-zA-Z0-9]+)/',
            ],
            'embed_template' => 'https://www.dailymotion.com/embed/video/{id}',
            'thumbnail_template' => 'https://www.dailymotion.com/thumbnail/video/{id}',
        ],
        'vimeo' => [
            'patterns' => [
                '/vimeo\.com\/(?:video\/)?(\d+)/',
                '/player\.vimeo\.com\/video\/(\d+)/',
            ],
            'embed_template' => 'https://player.vimeo.com/video/{id}?title=0&byline=0&portrait=0',
            'api_thumbnail' => true, // Requires API call for thumbnail
        ],
        'googledrive' => [
            'patterns' => [
                '/drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)/',
                '/drive\.google\.com\/open\?id=([a-zA-Z0-9_-]+)/',
                '/docs\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)/',
            ],
            'embed_template' => 'https://drive.google.com/file/d/{id}/preview',
            'thumbnail_template' => 'https://drive.google.com/thumbnail?id={id}&sz=w1280',
        ],
        'facebook' => [
            'patterns' => [
                '/facebook\.com\/(?:watch\/?\?v=|.*\/videos\/)(\d+)/',
                '/fb\.watch\/([a-zA-Z0-9_-]+)/',
            ],
            'embed_template' => 'https://www.facebook.com/plugins/video.php?href=https://www.facebook.com/watch/?v={id}&show_text=0',
        ],
        'twitter' => [
            'patterns' => [
                '/(?:twitter|x)\.com\/\w+\/status\/(\d+)/',
            ],
            'embed_template' => 'https://platform.twitter.com/embed/Tweet.html?id={id}',
        ],
        'streamable' => [
            'patterns' => [
                '/streamable\.com\/(?:e\/)?([a-zA-Z0-9]+)/',
            ],
            'embed_template' => 'https://streamable.com/e/{id}',
            'thumbnail_template' => 'https://cdn-cf-east.streamable.com/image/{id}.jpg',
        ],
        'twitch' => [
            'patterns' => [
                '/twitch\.tv\/videos\/(\d+)/',
                '/clips\.twitch\.tv\/([a-zA-Z0-9_-]+)/',
            ],
            'embed_template' => 'https://player.twitch.tv/?video={id}&parent=' . '{host}',
        ],
        'tiktok' => [
            'patterns' => [
                '/tiktok\.com\/@[^\/]+\/video\/(\d+)/',
                '/vm\.tiktok\.com\/([a-zA-Z0-9]+)/',
            ],
            'embed_template' => 'https://www.tiktok.com/embed/v2/{id}',
        ],
        'rumble' => [
            'patterns' => [
                '/rumble\.com\/embed\/([a-zA-Z0-9]+)/',
                '/rumble\.com\/([a-zA-Z0-9]+)-/',
            ],
            'embed_template' => 'https://rumble.com/embed/{id}/',
        ],
        'bitchute' => [
            'patterns' => [
                '/bitchute\.com\/(?:video|embed)\/([a-zA-Z0-9]+)/',
            ],
            'embed_template' => 'https://www.bitchute.com/embed/{id}/',
        ],
        'odysee' => [
            'patterns' => [
                '/odysee\.com\/\$\/embed\/([^\/]+\/[a-f0-9]+)/',
                '/odysee\.com\/@[^\/]+\/([^\/]+)/',
            ],
            'embed_template' => 'https://odysee.com/$/embed/{id}',
        ],
    ];

    /**
     * Parse an embed URL and extract platform information.
     * 
     * @param string $url The URL to parse
     * @return array|null Returns array with platform info or null if not supported
     */
    public function parseUrl(string $url): ?array
    {
        $url = trim($url);
        
        if (empty($url)) {
            return null;
        }

        // Normalize URL
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }

        foreach ($this->platforms as $platform => $config) {
            foreach ($config['patterns'] as $pattern) {
                if (preg_match($pattern, $url, $matches)) {
                    $videoId = $matches[1];
                    
                    return [
                        'platform' => $platform,
                        'video_id' => $videoId,
                        'original_url' => $url,
                        'embed_url' => $this->buildEmbedUrl($platform, $videoId, $config),
                        'thumbnail_url' => $this->buildThumbnailUrl($platform, $videoId, $config),
                    ];
                }
            }
        }

        // Try generic iframe embed detection
        if (preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/', $url, $matches)) {
            return [
                'platform' => 'iframe',
                'video_id' => md5($matches[1]),
                'original_url' => $url,
                'embed_url' => $matches[1],
                'thumbnail_url' => null,
            ];
        }

        return null;
    }

    /**
     * Build the embed iframe URL for a platform.
     */
    protected function buildEmbedUrl(string $platform, string $videoId, array $config): string
    {
        $template = $config['embed_template'];
        $url = str_replace('{id}', $videoId, $template);
        
        // Handle dynamic host for platforms that need it (like Twitch)
        if (str_contains($url, '{host}')) {
            $host = request()->getHost();
            $url = str_replace('{host}', $host, $url);
        }
        
        return $url;
    }

    /**
     * Build the thumbnail URL for a platform.
     */
    protected function buildThumbnailUrl(string $platform, string $videoId, array $config): ?string
    {
        if (!isset($config['thumbnail_template'])) {
            return null;
        }

        return str_replace('{id}', $videoId, $config['thumbnail_template']);
    }

    /**
     * Get thumbnail URL for a video (may need to fetch from API).
     */
    public function getThumbnailUrl(string $platform, string $videoId): ?string
    {
        if (!isset($this->platforms[$platform])) {
            return null;
        }

        $config = $this->platforms[$platform];

        // Try primary thumbnail URL
        $thumbnailUrl = $this->buildThumbnailUrl($platform, $videoId, $config);
        
        if ($thumbnailUrl && $this->isUrlAccessible($thumbnailUrl)) {
            return $thumbnailUrl;
        }

        // Try fallback thumbnail URL
        if (isset($config['thumbnail_fallback'])) {
            $fallbackUrl = str_replace('{id}', $videoId, $config['thumbnail_fallback']);
            if ($this->isUrlAccessible($fallbackUrl)) {
                return $fallbackUrl;
            }
        }

        // For Vimeo, try API (if configured)
        if ($platform === 'vimeo') {
            return $this->getVimeoThumbnail($videoId);
        }

        return null;
    }

    /**
     * Check if a URL is accessible.
     */
    protected function isUrlAccessible(string $url): bool
    {
        try {
            $headers = @get_headers($url);
            return $headers && str_contains($headers[0], '200');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get Vimeo thumbnail via oEmbed API.
     */
    protected function getVimeoThumbnail(string $videoId): ?string
    {
        try {
            $apiUrl = "https://vimeo.com/api/oembed.json?url=https://vimeo.com/{$videoId}";
            $response = @file_get_contents($apiUrl);
            
            if ($response) {
                $data = json_decode($response, true);
                return $data['thumbnail_url'] ?? null;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch Vimeo thumbnail', [
                'video_id' => $videoId,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Download and store thumbnail locally.
     */
    public function downloadThumbnail(string $url, string $uuid): ?string
    {
        try {
            $thumbnailContent = @file_get_contents($url);
            
            if (!$thumbnailContent) {
                return null;
            }

            $path = "videos/{$uuid}/thumb.jpg";
            $fullPath = storage_path('app/public/' . $path);
            
            // Ensure directory exists
            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($fullPath, $thumbnailContent);
            
            return $path;
        } catch (\Exception $e) {
            Log::warning('Failed to download thumbnail', [
                'url' => $url,
                'uuid' => $uuid,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get list of supported platforms for display.
     */
    public function getSupportedPlatforms(): array
    {
        return [
            'youtube' => 'YouTube',
            'dailymotion' => 'Dailymotion',
            'vimeo' => 'Vimeo',
            'googledrive' => 'Google Drive',
            'facebook' => 'Facebook',
            'twitter' => 'Twitter/X',
            'streamable' => 'Streamable',
            'twitch' => 'Twitch',
            'tiktok' => 'TikTok',
            'rumble' => 'Rumble',
            'bitchute' => 'BitChute',
            'odysee' => 'Odysee',
        ];
    }

    /**
     * Validate if a URL is from a supported platform.
     */
    public function isSupported(string $url): bool
    {
        return $this->parseUrl($url) !== null;
    }

    /**
     * Get platform display name.
     */
    public function getPlatformName(string $platform): string
    {
        return $this->getSupportedPlatforms()[$platform] ?? ucfirst($platform);
    }
}
