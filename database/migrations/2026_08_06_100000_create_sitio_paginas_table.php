<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Páginas del sitio público.
 *
 * El diagnóstico de la propuesta señaló como CRÍTICO que los diez servicios
 * compartieran una sola URL: ninguno podía posicionarse. Esta tabla existe para
 * que cada servicio tenga su propia página, su propio título y su propia meta
 * descripción — que es lo que Google necesita para clasificarlos por separado.
 *
 * Los campos de SEO viven junto al contenido y no en una tabla aparte porque
 * siempre se editan a la vez: quien escribe la página es quien decide con qué
 * título quiere aparecer en Google.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sitio_paginas', function (Blueprint $table) {
            $table->id();

            // landing = la portada (una sola), servicio = las páginas que
            // compiten en Google, noticia = el plan de contenidos.
            $table->string('tipo', 20)->default('servicio')->index();

            // La URL. Es dato de SEO: se elige con la palabra clave dentro
            // ("camaras-de-seguridad-cctv-bogota") y por eso no se deriva del
            // título — cambiar un titular no debe romper una URL posicionada.
            $table->string('slug')->unique();

            $table->string('titulo');
            $table->string('subtitulo')->nullable();
            $table->text('resumen')->nullable();
            $table->longText('contenido')->nullable();

            $table->string('imagen')->nullable();
            $table->string('icono', 60)->nullable();

            // --- SEO ---
            // Vacíos = se calculan solos desde el título y el resumen. Se
            // guardan aparte para poder escribir en Google algo distinto de lo
            // que se lee en la página, que es justo lo que pide el oficio.
            $table->string('seo_titulo')->nullable();
            $table->string('seo_descripcion', 320)->nullable();
            $table->string('seo_og_imagen')->nullable();
            $table->string('seo_palabra_clave')->nullable();
            $table->boolean('seo_noindex')->default(false);

            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamp('publicado_at')->nullable();

            $table->timestamps();

            // El sitemap y los menús piden lo mismo: lo publicado, en orden.
            $table->index(['tipo', 'activo', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sitio_paginas');
    }
};
