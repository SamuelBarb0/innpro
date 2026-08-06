<?php

namespace App\Http\Controllers;

use App\Models\SitioPagina;
use App\Support\Sitio;
use Illuminate\Http\Response;

/**
 * El sitio público: portada, páginas de servicio, noticias, sitemap y robots.
 */
class SitioController extends Controller
{
    public function portada()
    {
        $pagina = SitioPagina::publicadas()
            ->deTipo(SitioPagina::LANDING)
            ->with('bloques')
            ->orderBy('orden')
            ->firstOrFail();

        return view('landing', [
            'pagina' => $pagina,
            'servicios' => Sitio::menuServicios(),
        ]);
    }

    public function servicio(string $slug)
    {
        return $this->mostrar(SitioPagina::SERVICIO, $slug);
    }

    public function noticia(string $slug)
    {
        return $this->mostrar(SitioPagina::NOTICIA, $slug);
    }

    /**
     * Un método por tipo, en vez de uno solo con el tipo inyectado desde los
     * `defaults` de la ruta: ahí Laravel ataba el primer parámetro al primer
     * valor de la ruta —el slug— y la página nunca aparecía. Un 404 mudo por un
     * orden de argumentos es exactamente el fallo que no se quiere heredar en
     * las URLs que tienen que posicionar.
     */
    private function mostrar(string $tipo, string $slug)
    {
        $pagina = SitioPagina::publicadas()
            ->deTipo($tipo)
            ->with('bloques')
            ->where('slug', $slug)
            ->firstOrFail();

        return view('sitio.pagina', [
            'pagina' => $pagina,
            'servicios' => Sitio::menuServicios(),
        ]);
    }

    /**
     * Mapa del sitio para Google.
     *
     * Se genera en vivo desde la base y no como archivo estático porque el
     * cliente va a publicar páginas desde el panel: un sitemap que hay que
     * regenerar a mano es un sitemap que queda viejo la primera semana.
     *
     * Las páginas marcadas `noindex` se excluyen: pedirle a Google que indexe
     * en el sitemap lo que la propia página le prohíbe es una señal
     * contradictoria, y Search Console la reporta como error.
     */
    public function sitemap(): Response
    {
        $paginas = SitioPagina::publicadas()
            ->where('seo_noindex', false)
            ->orderByRaw("FIELD(tipo, 'landing', 'servicio', 'noticia')")
            ->orderBy('orden')
            ->get();

        $xml = view('sitio.sitemap', ['paginas' => $paginas])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * robots.txt, también en vivo: mientras el interruptor global de noindex
     * esté puesto se prohíbe TODO, que es lo correcto si el sitio aún no es el
     * oficial del dominio.
     */
    public function robots(): Response
    {
        if (Sitio::noindexGlobal()) {
            $cuerpo = "User-agent: *\nDisallow: /\n";
        } else {
            $cuerpo = "User-agent: *\n"
                ."Allow: /\n"
                // Nada de esto le sirve a Google y sí gasta su presupuesto de
                // rastreo en el sitio.
                ."Disallow: /login\n"
                ."Disallow: /dashboard\n"
                ."Disallow: /admin\n"
                ."Disallow: /catalogo\n"
                ."Disallow: /servicio/\n"
                ."\nSitemap: ".url('/sitemap.xml')."\n";
        }

        return response($cuerpo, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
