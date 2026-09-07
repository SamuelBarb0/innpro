<?php

namespace App\Http\Controllers;

use App\Models\Parametros;
use App\Models\SitioBloque;
use App\Models\SitioPagina;
use App\Models\SitioRedireccion;
use App\Support\PortadaEsquema;
use App\Support\Sitio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Panel del sitio público: contenido, SEO y redirecciones.
 *
 * Existe porque la propuesta promete que Innpro pueda «publicar contenido sin
 * depender del proveedor». Sin esto, cada cambio de un teléfono o de un título
 * en Google sería un despliegue.
 */
class SitioAdminController extends Controller
{
    /** Los ajustes editables, agrupados como se muestran en pantalla. */
    private const AJUSTES = [
        'Datos del negocio (NAP)' => [
            'sitio_nombre' => 'Nombre del negocio',
            // Lo pide la politica de privacidad: la ley 1581 obliga a
            // identificar al responsable del tratamiento de datos.
            'sitio_nit' => 'NIT',
            'sitio_direccion' => 'Dirección',
            'sitio_ciudad' => 'Ciudad',
            'sitio_telefono' => 'Teléfono fijo',
            'sitio_celular' => 'Celular',
            'sitio_email' => 'Correo comercial',
            'sitio_horario' => 'Horario de atención',
            'sitio_cobertura' => 'Zona de cobertura',
            // Se guarda el ANIO DE INICIO y no el total de anios: el total
            // envejece solo y hay que acordarse de subirlo cada enero, que es
            // como el sitio viejo acabo diciendo «13 anos» en la portada y
            // «mas de 9» en Nuestra Empresa. Escribiendo {anios} en cualquier
            // texto del panel, sale el numero calculado desde este anio.
            'sitio_anio_fundacion' => 'Año de inicio de operaciones',
        ],
        'Contacto y redes' => [
            'sitio_whatsapp' => 'WhatsApp (solo dígitos, con indicativo)',
            'sitio_facebook' => 'Facebook',
            'sitio_linkedin' => 'LinkedIn',
            'sitio_instagram' => 'Instagram',
        ],
        'Aviso de cookies' => [
            // El texto estaba escrito dentro de la plantilla, o sea que
            // corregir una coma exigía un despliegue. Los botones no se editan
            // a propósito: «Aceptar» y «Rechazar» son los términos que la gente
            // reconoce, y dejarlos sueltos invita a suavizar el de rechazar.
            'sitio_cookies_titulo' => 'Aviso de cookies · encabezado',
            'sitio_cookies_texto' => 'Aviso de cookies · texto',
        ],
        'Buscadores y medición' => [
            'sitio_ga4' => 'ID de Google Analytics 4',
            'sitio_search_console' => 'Verificación de Search Console',
        ],
    ];

    private function autorizar(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
    }

    // ───────────────────────── Ajustes ─────────────────────────

    public function ajustes()
    {
        $this->autorizar();

        $grupos = [];
        foreach (self::AJUSTES as $grupo => $claves) {
            foreach ($claves as $clave => $etiqueta) {
                $grupos[$grupo][$clave] = [
                    'etiqueta' => $etiqueta,
                    'valor' => Parametros::valor($clave, ''),
                    'ayuda' => Parametros::where('nombre_parametro', $clave)->value('comentario'),
                ];
            }
        }

        return view('sitio_admin.ajustes', [
            'grupos' => $grupos,
            'noindex' => Sitio::noindexGlobal(),
        ]);
    }

    public function guardarAjustes(Request $request): RedirectResponse
    {
        $this->autorizar();

        $claves = array_merge(...array_values(array_map('array_keys', self::AJUSTES)));

        foreach ($claves as $clave) {
            if (! $request->has($clave)) {
                continue;
            }

            Parametros::updateOrCreate(
                ['nombre_parametro' => $clave],
                ['valor_parametro' => (string) $request->input($clave), 'estado' => true],
            );
        }

        // El interruptor llega como checkbox: ausente = apagado.
        Parametros::updateOrCreate(
            ['nombre_parametro' => 'sitio_noindex'],
            ['valor_parametro' => $request->boolean('sitio_noindex') ? '1' : '0', 'estado' => true],
        );

        // Los ajustes se memorizan por proceso; sin esto el propio redirect
        // podría mostrar todavía los valores viejos.
        Sitio::olvidar();

        return back()->with('success', 'Ajustes del sitio actualizados.');
    }

    // ───────────────────────── Páginas ─────────────────────────

    public function paginas()
    {
        $this->autorizar();

        return view('sitio_admin.paginas', [
            // 'legal' va DENTRO del FIELD: lo que falta en la lista devuelve 0
            // y se ordena primero, así que las páginas legales encabezaban el
            // listado por delante de la portada.
            'paginas' => SitioPagina::orderByRaw("FIELD(tipo, 'landing', 'servicio', 'noticia', 'legal')")
                ->orderBy('orden')
                ->orderBy('titulo')
                ->get(),
        ]);
    }

    public function formPagina(?SitioPagina $pagina = null)
    {
        $this->autorizar();

        return view('sitio_admin.pagina_form', [
            'pagina' => $pagina ?: new SitioPagina(['tipo' => SitioPagina::SERVICIO, 'activo' => true]),
        ]);
    }

    public function guardarPagina(Request $request): RedirectResponse
    {
        $this->autorizar();

        $pagina = $request->id ? SitioPagina::findOrFail($request->id) : new SitioPagina();

        $datos = $request->validate([
            'id' => ['nullable', 'integer'],
            'tipo' => ['required', Rule::in(array_keys(SitioPagina::TIPOS))],
            // El slug es la URL y la URL es SEO: solo minúsculas, números y
            // guiones. Un slug con tildes o espacios sale escapado en el
            // navegador y se vuelve ilegible en los resultados de Google.
            'slug' => [
                'required', 'string', 'max:190', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('sitio_paginas', 'slug')->ignore($pagina->id),
            ],
            'titulo' => ['required', 'string', 'max:255'],
            'subtitulo' => ['nullable', 'string', 'max:255'],
            'resumen' => ['nullable', 'string', 'max:1000'],
            'contenido' => ['nullable', 'string'],
            'icono' => ['nullable', 'string', 'max:60'],
            'seo_titulo' => ['nullable', 'string', 'max:60'],
            'seo_descripcion' => ['nullable', 'string', 'max:155'],
            'seo_palabra_clave' => ['nullable', 'string', 'max:255'],
            'seo_og_imagen' => ['nullable', 'string', 'max:255'],
            'orden' => ['nullable', 'integer', 'min:0'],
        ], [
            'slug.regex' => 'La URL solo admite minúsculas, números y guiones (ej. camaras-de-seguridad-bogota).',
            'slug.unique' => 'Ya existe otra página con esa URL.',
            'seo_titulo.max' => 'Google corta el título a 60 caracteres: más largo se ve a medias.',
            'seo_descripcion.max' => 'Google corta la descripción a 155 caracteres.',
        ]);

        $datos['seo_noindex'] = $request->boolean('seo_noindex');
        $datos['activo'] = $request->boolean('activo');
        // Publicar es una decisión explícita; si se marca activo y nunca tuvo
        // fecha, se publica ahora en vez de quedar en un limbo invisible.
        $datos['publicado_at'] = $datos['activo'] ? ($pagina->publicado_at ?: now()) : $pagina->publicado_at;

        $pagina->fill($datos)->save();

        return redirect()->route('sitio.admin.paginas')->with('success', 'Página guardada.');
    }

    public function toggleActivoPagina(SitioPagina $pagina): RedirectResponse
    {
        $this->autorizar();

        $pagina->forceFill([
            'activo' => ! $pagina->activo,
            'publicado_at' => $pagina->publicado_at ?: now(),
        ])->save();

        return back()->with('success', $pagina->activo ? 'Página publicada.' : 'Página despublicada.');
    }

    public function eliminarPagina(SitioPagina $pagina): RedirectResponse
    {
        $this->autorizar();

        // La portada no se borra: sin ella la raíz del sitio devuelve 404.
        if ($pagina->tipo === SitioPagina::LANDING) {
            return back()->with('error', 'La portada no se puede eliminar. Si quieres cambiarla, edítala.');
        }

        $pagina->delete();

        return back()->with('success', 'Página eliminada.');
    }

    // ──────────────────── Secciones de la portada ────────────────────

    public function secciones()
    {
        $this->autorizar();

        $portada = SitioPagina::deTipo(SitioPagina::LANDING)->with('bloques')->firstOrFail();

        return view('sitio_admin.secciones', [
            'portada' => $portada,
            'esquema' => PortadaEsquema::bloques(),
        ]);
    }

    public function guardarSeccion(Request $request, SitioBloque $bloque): RedirectResponse
    {
        $this->autorizar();

        $datos = $request->validate([
            'antetitulo' => ['nullable', 'string', 'max:255'],
            'titulo' => ['nullable', 'string', 'max:255'],
            'subtitulo' => ['nullable', 'string', 'max:255'],
            'texto' => ['nullable', 'string', 'max:4000'],
        ]);

        $datos['activo'] = $request->boolean('activo');
        $datos['datos'] = $this->componerDatos($request, $bloque);

        $bloque->fill($datos)->save();

        return back()->with('success', 'Sección «'.PortadaEsquema::de($bloque->clave)['nombre'].'» actualizada.');
    }

    /**
     * Reconstruye la columna `datos` a partir del formulario, siguiendo el
     * esquema del bloque.
     *
     * Se compone desde cero en lugar de mezclar con lo que había, para que
     * borrar la última tarjeta de una lista signifique de verdad borrarla. Pero
     * SOLO se tocan las claves declaradas en el esquema: cualquier otra cosa
     * que alguien haya dejado en el JSON se conserva tal cual, porque este
     * formulario no sabe qué es y no le corresponde tirarla.
     */
    private function componerDatos(Request $request, SitioBloque $bloque): array
    {
        $esquema = PortadaEsquema::de($bloque->clave);
        $datos = $bloque->datos ?? [];
        $enviado = (array) $request->input('datos', []);

        // ── Grupos (un botón: texto + enlace) ──
        foreach ($esquema['grupos'] ?? [] as $clave => $_) {
            $grupo = (array) ($enviado[$clave] ?? []);
            $texto = trim((string) ($grupo['texto'] ?? ''));
            $url = trim((string) ($grupo['url'] ?? ''));

            // Un botón sin texto no se pinta, así que se guarda vacío entero:
            // dejar la URL suelta solo confunde a quien lo revise después.
            $datos[$clave] = $texto === '' ? [] : ['texto' => $texto, 'url' => $url];
        }

        // ── Listas (tarjetas, cifras, viñetas) ──
        foreach ($esquema['listas'] ?? [] as $clave => $lista) {
            $filas = [];

            foreach ((array) ($enviado[$clave] ?? []) as $fila) {
                if (! is_array($fila)) {
                    continue;
                }

                $limpia = [];
                foreach ($lista['campos'] as $campo => $def) {
                    $valor = $fila[$campo] ?? '';

                    if (($def['tipo'] ?? 'text') === 'lineas') {
                        // Un textarea de «una cosa por línea» se guarda como
                        // lista. Se descartan las líneas en blanco: si no, un
                        // salto de más pinta una viñeta vacía en la portada.
                        $limpia[$campo] = array_values(array_filter(array_map(
                            'trim',
                            preg_split('/\r\n|\r|\n/', (string) $valor) ?: []
                        ), fn ($l) => $l !== ''));

                        continue;
                    }

                    $limpia[$campo] = trim((string) $valor);
                }

                // Una fila entera en blanco es una fila que se acaba de añadir
                // y no se llegó a rellenar. No se guarda.
                $tieneAlgo = collect($limpia)->contains(fn ($v) => is_array($v) ? $v !== [] : $v !== '');
                if ($tieneAlgo) {
                    $filas[] = $limpia;
                }
            }

            if (isset($lista['max'])) {
                $filas = array_slice($filas, 0, (int) $lista['max']);
            }

            $datos[$clave] = $filas;
        }

        return $datos;
    }

    // ──────────────────────── Redirecciones ────────────────────────

    public function redirecciones()
    {
        $this->autorizar();

        return view('sitio_admin.redirecciones', [
            'redirecciones' => SitioRedireccion::orderByDesc('golpes')->orderBy('origen')->get(),
        ]);
    }

    public function guardarRedireccion(Request $request): RedirectResponse
    {
        $this->autorizar();

        $regla = $request->id ? SitioRedireccion::findOrFail($request->id) : new SitioRedireccion();

        $request->validate([
            'id' => ['nullable', 'integer'],
            'origen' => ['required', 'string', 'max:255'],
            'destino' => ['required', 'string', 'max:255'],
            'codigo' => ['required', Rule::in([301, 302])],
        ]);

        // Se normaliza al guardar y no al comparar: así una URL copiada de
        // Search Console con dominio y barra final encaja igual que escrita a
        // mano, y el listado muestra siempre la misma forma.
        $origen = SitioRedireccion::normalizar($request->origen);

        if (SitioRedireccion::where('origen', $origen)->where('id', '!=', $regla->id)->exists()) {
            return back()->with('error', 'Ya existe una redirección para «'.$origen.'».');
        }

        $regla->fill([
            'origen' => $origen,
            'destino' => $request->destino,
            'codigo' => (int) $request->codigo,
            'activo' => $request->boolean('activo', true),
        ])->save();

        return back()->with('success', 'Redirección guardada.');
    }

    public function eliminarRedireccion(SitioRedireccion $redireccion): RedirectResponse
    {
        $this->autorizar();

        $redireccion->delete();

        return back()->with('success', 'Redirección eliminada.');
    }
}
