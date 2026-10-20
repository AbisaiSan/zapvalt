<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeçalhos de segurança em todas as respostas da aplicação.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // A página de erro detalhada do Laravel (só com APP_DEBUG) usa scripts inline.
        $debugError = config('app.debug') && $response->getStatusCode() >= 500;

        if (! $debugError) {
            $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());
        }

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        $directives = [
            'default-src' => ["'self'"],
            'img-src' => ["'self'", 'data:', 'blob:'],
            'media-src' => ["'self'", 'blob:'],
            'frame-src' => ["'self'"],
            'script-src' => ["'self'"],
        ];

        // Em desenvolvimento (npm run dev), libera apenas o servidor local do Vite.
        if (Vite::isRunningHot()) {
            $origin = rtrim(trim((string) file_get_contents(Vite::hotFile())), '/');
            $socket = preg_replace('#^http#', 'ws', $origin);

            $directives['script-src'][] = $origin;
            $directives['style-src'] = ["'self'", "'unsafe-inline'", $origin];
            $directives['connect-src'] = ["'self'", $origin, $socket];
        }

        return collect($directives)
            ->map(fn (array $sources, string $name) => $name.' '.implode(' ', $sources))
            ->implode('; ');
    }
}
