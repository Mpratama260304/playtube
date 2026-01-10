<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;

class VideoStreamController extends Controller
{
    /**
     * Stream video with full RFC 7233 HTTP Range support.
     * 
     * Supports:
     * - bytes=START-END (standard range)
     * - bytes=START- (from START to end)
     * - bytes=-SUFFIX (last N bytes - CRITICAL for MP4 moov atom)
     * - HEAD requests (headers only)
     * 
     * This enables smooth seeking and fast playback start in all browsers.
     */
    public function stream(Request $request, Video $video)
    {
        // Check if video can be viewed
        if (!$video->canBeViewedBy(auth()->user())) {
            abort(404);
        }

        // Determine which file to serve
        $quality = $request->get('quality');
        $filePath = $this->resolveFilePath($video, $quality);

        if (!$filePath) {
            abort(404, 'Video file not found');
        }

        $disk = Storage::disk('public');
        
        if (!$disk->exists($filePath)) {
            abort(404, 'Video file not found');
        }

        $fullPath = $disk->path($filePath);
        $mimeType = $this->getMimeType($filePath);

        // Production: Use Nginx X-Accel-Redirect for optimal performance
        if ($this->shouldUseXAccelRedirect()) {
            return $this->streamViaXAccel($filePath, $mimeType, $request);
        }

        // Development fallback: PHP streaming with full Range support
        return $this->streamViaPhp($fullPath, $request, $mimeType);
    }

    /**
     * Resolve which file to serve based on quality parameter
     */
    protected function resolveFilePath(Video $video, ?string $quality): ?string
    {
        // If quality is specified (e.g., ?quality=360 or ?quality=720)
        if ($quality && $video->renditions && isset($video->renditions[$quality])) {
            $rendition = $video->renditions[$quality];
            if (isset($rendition['path'])) {
                return $rendition['path'];
            }
        }

        // Default priority: stream_path (faststart) > original_path
        if ($video->stream_ready && $video->stream_path) {
            return $video->stream_path;
        }

        return $video->original_path;
    }

    /**
     * Check if we should use X-Accel-Redirect (production)
     */
    protected function shouldUseXAccelRedirect(): bool
    {
        return config('playtube.video_delivery_driver') === 'nginx';
    }

    /**
     * Stream via Nginx X-Accel-Redirect (production)
     * Nginx handles Range requests natively with sendfile.
     */
    protected function streamViaXAccel(string $relativePath, string $mimeType, Request $request): Response
    {
        // Nginx internal location: /_protected_storage/ -> storage/app/public/
        $internalPath = '/_protected_storage/' . ltrim($relativePath, '/');

        $headers = [
            'Content-Type' => $mimeType,
            'X-Accel-Redirect' => $internalPath,
            'X-Accel-Buffering' => 'no',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Content-Disposition' => 'inline',
        ];

        return response('', 200, $headers);
    }

    /**
     * Stream via PHP with full RFC 7233 Range support.
     * 
     * Supports:
     * - bytes=START-END
     * - bytes=START-
     * - bytes=-SUFFIX (critical for MP4 moov atom seeking)
     */
    protected function streamViaPhp(string $fullPath, Request $request, string $mimeType): Response
    {
        // Disable output buffering/compression for streaming
        @ini_set('zlib.output_compression', 'Off');
        
        $size = filesize($fullPath);
        
        if ($size === false || $size === 0) {
            abort(404, 'Video file is empty or unreadable');
        }

        // Default: serve entire file
        $start = 0;
        $end = $size - 1;
        $statusCode = 200;

        // Parse Range header (RFC 7233)
        $rangeHeader = $request->header('Range');
        $rangeResult = $this->parseRangeHeader($rangeHeader, $size);
        
        if ($rangeResult !== null) {
            if ($rangeResult === false) {
                // Invalid range - return 416 Range Not Satisfiable
                return response('', 416, [
                    'Content-Range' => "bytes */$size",
                    'Accept-Ranges' => 'bytes',
                ]);
            }
            
            // Valid range
            $start = $rangeResult['start'];
            $end = $rangeResult['end'];
            $statusCode = 206;
        }

        $length = $end - $start + 1;

        // Handle HEAD requests - return headers only, no body
        if ($request->isMethod('HEAD')) {
            $headers = $this->buildStreamHeaders($mimeType, $size, $start, $end, $length, $statusCode);
            return response('', $statusCode, $headers);
        }

        // Stream the video content
        $response = new StreamedResponse(function () use ($fullPath, $start, $length) {
            // Additional output buffering disable inside closure
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            $handle = fopen($fullPath, 'rb');
            if (!$handle) {
                return;
            }
            
            fseek($handle, $start);
            
            // Use smaller chunks for better responsiveness (256KB)
            $bufferSize = 256 * 1024;
            $remaining = $length;
            
            while ($remaining > 0 && !feof($handle) && connection_status() === CONNECTION_NORMAL) {
                $readLength = min($bufferSize, $remaining);
                $data = fread($handle, $readLength);
                
                if ($data === false) {
                    break;
                }
                
                echo $data;
                $remaining -= strlen($data);
                
                // Flush output immediately
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            }
            
            fclose($handle);
        }, $statusCode);

        // Set all headers
        $headers = $this->buildStreamHeaders($mimeType, $size, $start, $end, $length, $statusCode);
        
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }

    /**
     * Parse HTTP Range header according to RFC 7233.
     * 
     * Supported formats:
     * - bytes=START-END (e.g., bytes=0-1023)
     * - bytes=START- (e.g., bytes=1024-)
     * - bytes=-SUFFIX (e.g., bytes=-1024) - returns last N bytes
     * 
     * @param string|null $rangeHeader The Range header value
     * @param int $size Total file size
     * @return array|false|null Returns ['start' => int, 'end' => int] on success,
     *                          false for invalid range, null if no range requested
     */
    protected function parseRangeHeader(?string $rangeHeader, int $size): array|false|null
    {
        if (empty($rangeHeader)) {
            return null;
        }

        // Must start with "bytes="
        if (!preg_match('/^bytes=/i', $rangeHeader)) {
            return null;
        }

        // Remove "bytes=" prefix and get range spec
        $rangeSpec = substr($rangeHeader, 6);
        
        // Handle multiple ranges (comma-separated) - we only serve the first one
        // This is valid per RFC 7233, server can choose to ignore multipart
        if (strpos($rangeSpec, ',') !== false) {
            $ranges = explode(',', $rangeSpec);
            $rangeSpec = trim($ranges[0]);
        }
        
        $rangeSpec = trim($rangeSpec);

        // Case 1: Suffix range bytes=-N (last N bytes)
        // CRITICAL: This is what browsers use to fetch MP4 moov atom
        if (preg_match('/^-(\d+)$/', $rangeSpec, $matches)) {
            $suffixLength = intval($matches[1]);
            
            if ($suffixLength <= 0) {
                return false;
            }
            
            // If suffix is larger than file, return entire file
            if ($suffixLength >= $size) {
                return ['start' => 0, 'end' => $size - 1];
            }
            
            return [
                'start' => $size - $suffixLength,
                'end' => $size - 1,
            ];
        }

        // Case 2: Standard range bytes=START-END or bytes=START-
        if (preg_match('/^(\d+)-(\d*)$/', $rangeSpec, $matches)) {
            $start = intval($matches[1]);
            $end = !empty($matches[2]) ? intval($matches[2]) : $size - 1;
            
            // Validate range
            if ($start < 0 || $start >= $size) {
                return false;
            }
            
            // Cap end to file size
            if ($end >= $size) {
                $end = $size - 1;
            }
            
            // Start must not exceed end
            if ($start > $end) {
                return false;
            }
            
            return ['start' => $start, 'end' => $end];
        }

        // Invalid range format
        return false;
    }

    /**
     * Build headers for streaming response.
     */
    protected function buildStreamHeaders(
        string $mimeType,
        int $size,
        int $start,
        int $end,
        int $length,
        int $statusCode
    ): array {
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Length' => $length,
            'Accept-Ranges' => 'bytes',
            'Content-Encoding' => 'identity',
            'X-Accel-Buffering' => 'no',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Content-Disposition' => 'inline',
        ];

        // Add Content-Range for partial content (206)
        if ($statusCode === 206) {
            $headers['Content-Range'] = "bytes $start-$end/$size";
        }

        return $headers;
    }

    /**
     * Get MIME type for video file
     */
    protected function getMimeType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        return match($extension) {
            'mp4', 'm4v' => 'video/mp4',
            'webm' => 'video/webm',
            'ogv' => 'video/ogg',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'mkv' => 'video/x-matroska',
            default => 'video/mp4',
        };
    }
}
