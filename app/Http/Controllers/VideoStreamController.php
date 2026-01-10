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
     * Stream video with HTTP Range support for smooth seeking/playback
     * Supports quality parameter for multi-quality playback
     * Uses X-Accel-Redirect in production (nginx) for optimal performance
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

        $mimeType = $this->getMimeType($filePath);

        // Production: Use Nginx X-Accel-Redirect for optimal performance
        if ($this->shouldUseXAccelRedirect()) {
            return $this->streamViaXAccel($filePath, $mimeType);
        }

        // Development fallback: PHP streaming with Range support
        return $this->streamViaPhp($disk->path($filePath), $request, $mimeType);
    }

    /**
     * Resolve which file to serve based on quality parameter
     */
    protected function resolveFilePath(Video $video, ?string $quality): ?string
    {
        // If quality is specified and rendition exists
        if ($quality && $video->renditions && isset($video->renditions[$quality])) {
            $rendition = $video->renditions[$quality];
            if (isset($rendition['path'])) {
                return $rendition['path'];
            }
        }

        // Default: use stream_path if ready, otherwise original
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
        return config('playtube.video_delivery_driver') === 'nginx' 
            || config('app.env') === 'production';
    }

    /**
     * Stream via Nginx X-Accel-Redirect (production)
     */
    protected function streamViaXAccel(string $relativePath, string $mimeType): Response
    {
        // Nginx internal location: /_protected_storage/ -> storage/app/public/
        $internalPath = '/_protected_storage/' . ltrim($relativePath, '/');

        return response('', 200, [
            'Content-Type' => $mimeType,
            'X-Accel-Redirect' => $internalPath,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'Content-Disposition' => 'inline',
        ]);
    }

    /**
     * Stream via PHP with Range support (development fallback)
     */
    protected function streamViaPhp(string $fullPath, Request $request, string $mimeType): StreamedResponse
    {
        $size = filesize($fullPath);
        
        // Default: serve entire file
        $start = 0;
        $end = $size - 1;
        $length = $size;
        $statusCode = 200;

        // Handle Range requests for seeking
        $rangeHeader = $request->header('Range');
        if ($rangeHeader) {
            // Parse Range header: bytes=0-1023 or bytes=0-
            if (preg_match('/bytes=(\d+)-(\d*)/', $rangeHeader, $matches)) {
                $start = intval($matches[1]);
                $end = !empty($matches[2]) ? intval($matches[2]) : $size - 1;
                
                // Validate range
                if ($start > $end || $start >= $size || $end >= $size) {
                    return response('', 416, [
                        'Content-Range' => "bytes */$size",
                    ]);
                }
                
                $length = $end - $start + 1;
                $statusCode = 206;
            }
        }

        // Stream the video
        $response = new StreamedResponse(function () use ($fullPath, $start, $length) {
            $handle = fopen($fullPath, 'rb');
            fseek($handle, $start);
            
            $bufferSize = 1024 * 1024; // 1MB chunks
            $remaining = $length;
            
            while ($remaining > 0 && !feof($handle)) {
                $readLength = min($bufferSize, $remaining);
                echo fread($handle, $readLength);
                $remaining -= $readLength;
                flush();
            }
            
            fclose($handle);
        }, $statusCode);

        // Set headers
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Length' => $length,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=31536000',
            'Content-Disposition' => 'inline',
        ];

        if ($statusCode === 206) {
            $headers['Content-Range'] = "bytes $start-$end/$size";
        }

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
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
