<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * P1-02: security headers terpusat.
 * HSTS hanya saat HTTPS agar tidak mengunci http://localhost dev.
 * CSP disengaja longgar (unsafe-inline) karena Livewire + Tailwind CDN
 * memakai inline script/style; pengetatan nonce adalah follow-up P2.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(self), microphone=(), geolocation=(), payment=()'
        );

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (! $response->headers->has('Content-Security-Policy')) {
            // Layout admin masih memakai CDN (tailwind, lucide, alpine) —
            // tetap diizinkan eksplisit agar tampilan tidak rusak.
            // Hanya html5-qrcode yang di-vendor lokal (P2-03).
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://unpkg.com https://cdn.jsdelivr.net",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                "font-src 'self' https://fonts.gstatic.com",
                "img-src 'self' data: blob:",
                "media-src 'self' blob:",
                "connect-src 'self' ws: wss:",
                'frame-ancestors \'self\'',
                "base-uri 'self'",
                "form-action 'self'",
            ]));
        }

        // Jangan cache halaman sensitif vote/admin.
        if ($request->is('vote/*') || $request->is('admin/*')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
