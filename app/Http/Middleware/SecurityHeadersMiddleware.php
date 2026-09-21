<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);

        $response = $next($request);
        $scriptSources = ["'self'", "'nonce-{$nonce}'"];
        $styleSources = ["'self'"];
        $connectSources = ["'self'"];

        if (app()->isLocal()) {
            $scriptSources[] = 'http://localhost:5173';
            $scriptSources[] = 'http://127.0.0.1:5173';
            $styleSources[] = "'unsafe-inline'";
            $connectSources[] = 'ws://localhost:5173';
            $connectSources[] = 'ws://127.0.0.1:5173';
        }

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "img-src 'self' data:",
            "font-src 'self' data:",
            'style-src '.implode(' ', $styleSources),
            'script-src '.implode(' ', $scriptSources),
            'connect-src '.implode(' ', $connectSources),
        ]));
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), geolocation=(), microphone=(), payment=(), usb=()');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($request->user() !== null || $request->is('forgot-password', 'login', 'register', 'reset-password/*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
