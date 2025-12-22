<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttpsScheme
{
    /**
     * Handle an incoming request.
     *
     * When behind a reverse proxy (Cloudflare, trycloudflare, ngrok, etc.),
     * ensure Laravel correctly detects HTTPS and sets secure cookies.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If request is detected as secure (via X-Forwarded-Proto header after TrustProxies),
        // ensure session cookies are also secure
        if ($request->isSecure()) {
            config(['session.secure' => true]);
        }

        return $next($request);
    }
}
