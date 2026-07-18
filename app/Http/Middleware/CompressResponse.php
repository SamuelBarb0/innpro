<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pre-comprime la respuesta HTML con gzip desde la aplicación y fija
 * Content-Encoding + Content-Length. Evita que el CDN de Hostinger (hcdn)
 * re-comprima con zstd, cuyo stream multi-frame algunos navegadores
 * (Chrome/Brave) descomprimen mal y dejan la página en blanco (solo ";").
 */
class CompressResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo respuestas HTML "normales" (no streamed, no binarias, no ya codificadas)
        if (
            ! $response instanceof \Illuminate\Http\Response
            || $response->headers->has('Content-Encoding')
            || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')
        ) {
            return $response;
        }

        $accept = (string) $request->header('Accept-Encoding', '');
        if (! str_contains($accept, 'gzip') || ! function_exists('gzencode')) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content) || strlen($content) < 512) {
            return $response;
        }

        $gz = gzencode($content, 5);
        if ($gz === false) {
            return $response;
        }

        $response->setContent($gz);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', (string) strlen($gz));
        $response->headers->set('Vary', 'Accept-Encoding');
        // Pide a los intermediarios que no re-transformen el cuerpo ya codificado
        $response->headers->set('Cache-Control', trim($response->headers->get('Cache-Control', '') . ', no-transform', ', '));

        return $response;
    }
}
