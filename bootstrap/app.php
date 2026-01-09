<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Symfony\Component\HttpFoundation\Response;

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
        // Handle file too large exception
        $exceptions->render(function (PostTooLargeException $e, $request) {
            $maxSize = ini_get('post_max_size');
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "The uploaded file is too large. Maximum allowed size is {$maxSize}.",
                ], Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
            }
            
            return back()
                ->withInput()
                ->withErrors(['video' => "The uploaded file is too large. Maximum allowed size is {$maxSize}."]);
        });
    })->create();
