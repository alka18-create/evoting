<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * P1-02: security headers terpusat.
 * HSTS hanya saat HTTPS agar tidak mengunci http://localhost dev.
 * CSP: unsafe-eval dihapus (tidak dibutuhkan Livewire/Tailwind);
 * unsafe-inline dipertahankan sementara karena Livewire + Tailwind CDN
 * memakai inline script/style — migrasi nonce per-request adalah follow-up.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Nonce per-request untuk migrasi CSP ketat (dibagikan ke view).
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);
        try {
            \Illuminate\Support\Facades\View::share('cspNonce', $nonce);
        } catch (\Throwable) {
        }

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
            // Local/dev: izinkan eval (dibutuhkan Alpine) dan inline script/style
            // agar tidak repot mengelola nonce selama pengembangan.
            if (app()->environment('local')) {
                $response->headers->set('Content-Security-Policy', implode('; ', [
                    "default-src 'self'",
                    "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
                    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                    "font-src 'self' https://fonts.gstatic.com",
                    "img-src 'self' data: blob:",
                    "media-src 'self' blob:",
                    "connect-src 'self' ws: wss:",
                    "object-src 'none'",
                    "frame-ancestors 'self'",
                    "base-uri 'self'",
                    "form-action 'self'",
                ]));
            } else {
                // Production: nonce-based; inline script/style wajib pakai nonce.
                $response->headers->set('Content-Security-Policy', implode('; ', [
                    "default-src 'self'",
                    "script-src 'self' 'nonce-{$nonce}'",
                    "style-src 'self' 'nonce-{$nonce}' https://fonts.googleapis.com",
                    "font-src 'self' https://fonts.gstatic.com",
                    "img-src 'self' data: blob:",
                    "media-src 'self' blob:",
                    "connect-src 'self' ws: wss:",
                    "object-src 'none'",
                    "frame-ancestors 'self'",
                    "base-uri 'self'",
                    "form-action 'self'",
                ]));
            }
        }

        // Jangan cache halaman sensitif vote/admin.
        if ($request->is('vote/*') || $request->is('admin/*')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
