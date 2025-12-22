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
     */
    public function stream(Request $request, Video $video)
    {
        // Check if video can be viewed
        if (!$video->canBeViewedBy(auth()->user())) {
            abort(404);
        }

        if (!$video->original_path) {
            abort(404, 'Video file not found');
        }

        $disk = Storage::disk('public');
        
        if (!$disk->exists($video->original_path)) {
            abort(404, 'Video file not found');
        }

        $path = $disk->path($video->original_path);
        $size = filesize($path);
        $mimeType = $this->getMimeType($path);
        
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
        $response = new StreamedResponse(function () use ($path, $start, $length) {
            $handle = fopen($path, 'rb');
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
     * Stream HLS playlist or segment
     */
    public function streamHls(Request $request, Video $video, string $file)
    {
        // Check if video can be viewed
        if (!$video->canBeViewedBy(auth()->user())) {
            abort(404);
        }

        // Sanitize file path to prevent directory traversal
        $file = basename($file);
        $hlsDir = "videos/{$video->uuid}/hls";
        $path = "{$hlsDir}/{$file}";

        $disk = Storage::disk('public');
        
        if (!$disk->exists($path)) {
            abort(404, 'HLS file not found');
        }

        $fullPath = $disk->path($path);
        $content = file_get_contents($fullPath);
        
        // Determine content type
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $contentType = match($extension) {
            'm3u8' => 'application/vnd.apple.mpegurl',
            'ts' => 'video/mp2t',
            default => 'application/octet-stream',
        };

        return response($content, 200, [
            'Content-Type' => $contentType,
            'Content-Length' => strlen($content),
            'Cache-Control' => 'public, max-age=31536000',
            'Access-Control-Allow-Origin' => '*',
        ]);
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
