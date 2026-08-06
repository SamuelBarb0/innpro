<?php

namespace App\Models;

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

    public const TIPOS = [
        self::LANDING => 'Portada',
        self::SERVICIO => 'Página de servicio',
        self::NOTICIA => 'Noticia',
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
        $propio = trim((string) $this->seo_titulo);

        if ($propio !== '') {
            return Str::limit($propio, 60, '');
        }

        $empresa = Parametros::valor('empresa_nombre', 'Innpro Ingeniería');

        return Str::limit(trim($this->titulo).' | '.$empresa, 60, '');
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
        $propia = trim((string) $this->seo_descripcion);

        if ($propia !== '') {
            return Str::limit($propia, 155, '');
        }

        $base = trim(strip_tags((string) ($this->resumen ?: $this->subtitulo ?: $this->titulo)));

        return Str::limit($base, 155, '');
    }

    /** URL absoluta y canónica de la página. */
    public function url(): string
    {
        if ($this->tipo === self::LANDING) {
            return url('/');
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
            default => '0.6',
        };
    }

    public function frecuenciaSitemap(): string
    {
        return $this->tipo === self::NOTICIA ? 'monthly' : 'weekly';
    }
}
