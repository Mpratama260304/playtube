<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Parse PHP size string (e.g., "128M", "2G") to bytes
 */
function parsePhpSize(string $size): int
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

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust all proxies (Cloudflare, reverse proxies, tunnels)
        $middleware->trustProxies(at: '*');
        
        // Auto-detect HTTPS and set secure cookies accordingly
        $middleware->prepend(\App\Http\Middleware\ForceHttpsScheme::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle file too large exception with detailed info
        $exceptions->render(function (PostTooLargeException $e, $request) {
            $postMaxSize = ini_get('post_max_size');
            $uploadMaxFilesize = ini_get('upload_max_filesize');
            
            // Determine which limit is being hit
            $serverLimit = min(
                parsePhpSize($postMaxSize),
                parsePhpSize($uploadMaxFilesize)
            );
            $serverLimitMb = round($serverLimit / 1024 / 1024);
            
            $message = "Upload rejected by server limit. Maximum allowed: {$serverLimitMb}MB (PHP post_max_size: {$postMaxSize}, upload_max_filesize: {$uploadMaxFilesize}). "
                     . "To upload larger files, the server administrator must increase PHP limits.";
            
            \Log::warning('PostTooLargeException caught', [
                'content_length' => $request->header('Content-Length'),
                'post_max_size' => $postMaxSize,
                'upload_max_filesize' => $uploadMaxFilesize,
                'user_id' => auth()->id(),
                'uri' => $request->getRequestUri(),
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'error_type' => 'server_limit',
                    'server_limits' => [
                        'post_max_size' => $postMaxSize,
                        'upload_max_filesize' => $uploadMaxFilesize,
                    ],
                ], Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
            }
            
            return back()
                ->withInput()
                ->withErrors(['video' => $message]);
        });
    })->create();
