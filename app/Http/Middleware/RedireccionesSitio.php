<?php

namespace App\Http\Middleware;

use App\Models\SitioRedireccion;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Redirige las URLs del WordPress viejo a su equivalente en el sitio nuevo.
 *
 * Corre DESPUÉS de la ruta, no antes: solo se consulta la tabla cuando la
 * petición ya terminó en 404. Así una redirección mal escrita no puede
 * secuestrar una URL que sí existe, y el sitio no paga una consulta a la base
 * en cada visita —que es lo que costaría comprobarlo por adelantado—.
 */
class RedireccionesSitio
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        if ($respuesta->getStatusCode() !== 404 || ! $request->isMethod('GET')) {
            return $respuesta;
        }

        try {
            $origen = SitioRedireccion::normalizar($request->path());

            $regla = SitioRedireccion::where('activo', true)
                ->where('origen', $origen)
                ->first();
        } catch (Throwable $e) {
            // Si la tabla aún no existe (despliegue a medias), un 404 normal es
            // mejor que un error 500 en toda la web.
            return $respuesta;
        }

        if (! $regla) {
            return $respuesta;
        }

        $regla->registrarGolpe();

        $destino = str_starts_with($regla->destino, 'http') ? $regla->destino : url($regla->destino);

        return redirect()->away($destino, $regla->codigo);
    }
}
