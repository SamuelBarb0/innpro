<?php

namespace App\Models;

use App\Support\Sitio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Una página del sitio público: la portada, una página de servicio o una noticia.
 */
class SitioPagina extends Model
{
    protected $table = 'sitio_paginas';

    public const LANDING = 'landing';

    public const SERVICIO = 'servicio';

    public const NOTICIA = 'noticia';

    /**
     * Páginas legales (privacidad, cookies). Tipo propio y no una noticia
     * porque se sirven en la raíz —`/politica-de-privacidad`— y no bajo un
     * prefijo: el cliente pidió expresamente una URL legible, y `/noticias/
     * politica-de-privacidad` no lo es.
     */
    public const LEGAL = 'legal';

    public const TIPOS = [
        self::LANDING => 'Portada',
        self::SERVICIO => 'Página de servicio',
        self::NOTICIA => 'Noticia',
        self::LEGAL => 'Página legal',
    ];

    protected $fillable = [
        'tipo', 'slug', 'titulo', 'subtitulo', 'resumen', 'contenido', 'imagen', 'icono',
        'seo_titulo', 'seo_descripcion', 'seo_og_imagen', 'seo_palabra_clave', 'seo_noindex',
        'orden', 'activo', 'publicado_at',
    ];

    protected $casts = [
        'seo_noindex' => 'boolean',
        'activo' => 'boolean',
        'publicado_at' => 'datetime',
        'orden' => 'integer',
    ];

    public function bloques(): HasMany
    {
        return $this->hasMany(SitioBloque::class, 'pagina_id')->orderBy('orden');
    }

    /** La sección con esta clave, o null. Es lo que usa la plantilla. */
    public function bloque(string $clave): ?SitioBloque
    {
        return $this->bloques->firstWhere('clave', $clave);
    }

    public function scopePublicadas($q)
    {
        return $q->where('activo', true)
            ->where(fn ($s) => $s->whereNull('publicado_at')->orWhere('publicado_at', '<=', now()));
    }

    public function scopeDeTipo($q, string $tipo)
    {
        return $q->where('tipo', $tipo);
    }

    /**
     * El título que se ve en la pestaña del navegador y en el resultado de
     * Google. Si no se escribió uno propio se arma con la ciudad, porque el
     * hallazgo del diagnóstico fue justo ese: la portada se titulaba
     * «Mantenimiento y venta de equipos industriales», sin mencionar seguridad
     * electrónica ni Bogotá.
     *
     * Se recorta a 60 caracteres porque Google corta ahí: un título más largo
     * se muestra a medias y pierde precisamente el final, donde suele ir la
     * ciudad.
     */
    public function tituloSeo(): string
    {
        // Los marcadores se resuelven ANTES de recortar: «{anios}» ocupa 7
        // caracteres y «15» dos, así que medir sobre el texto sin resolver
        // recortaría el título por donde no es —y lo que se pierde al final es
        // justo la ciudad.
        $propio = trim(Sitio::txt($this->seo_titulo));

        if ($propio !== '') {
            return Str::limit($propio, 60, '');
        }

        $empresa = Parametros::valor('empresa_nombre', 'Innpro Ingeniería');

        return Str::limit(trim(Sitio::txt($this->titulo)).' | '.$empresa, 60, '');
    }

    /**
     * La descripción bajo el título en Google. Ninguna página del sitio viejo
     * tenía una, así que Google inventaba el resumen — con lo que el primer
     * mensaje que leía un comprador no lo escribía nadie de Innpro.
     *
     * 155 caracteres es donde corta el buscador.
     */
    public function descripcionSeo(): string
    {
        $propia = trim(Sitio::txt($this->seo_descripcion));

        if ($propia !== '') {
            return Str::limit($propia, 155, '');
        }

        $base = trim(strip_tags(Sitio::txt($this->resumen ?: $this->subtitulo ?: $this->titulo)));

        return Str::limit($base, 155, '');
    }

    /** URL absoluta y canónica de la página. */
    public function url(): string
    {
        if ($this->tipo === self::LANDING) {
            return url('/');
        }

        if ($this->tipo === self::LEGAL) {
            return url("/{$this->slug}");
        }

        $prefijo = $this->tipo === self::NOTICIA ? 'noticias' : 'servicios';

        return url("/{$prefijo}/{$this->slug}");
    }

    /**
     * Cada cuánto cambia y cuánto pesa, para el sitemap. La portada se toca más
     * que una página de servicio, y las de servicio son las que se quiere
     * empujar: por eso llevan más prioridad que las noticias.
     */
    public function prioridadSitemap(): string
    {
        return match ($this->tipo) {
            self::LANDING => '1.0',
            self::SERVICIO => '0.9',
            // Las legales tienen que existir y ser accesibles, pero no compiten
            // por ninguna búsqueda: van al final de la cola de rastreo.
            self::LEGAL => '0.2',
            default => '0.6',
        };
    }

    public function frecuenciaSitemap(): string
    {
        return match ($this->tipo) {
            self::NOTICIA => 'monthly',
            self::LEGAL => 'yearly',
            default => 'weekly',
        };
    }
}
