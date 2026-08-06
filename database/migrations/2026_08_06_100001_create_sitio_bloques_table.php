<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloques de contenido de una página.
 *
 * La portada no es un texto corrido: es una secuencia de secciones (hero,
 * servicios, empresa, lineamientos, experiencia, contacto) y cada una tiene sus
 * propios campos. Guardarla como un solo HTML obligaría a editar etiquetas a mano
 * —justo lo que se quiere evitar— y guardarla como columnas fijas obligaría a
 * migrar la base cada vez que se agregue una sección.
 *
 * `clave` identifica la sección dentro de la página (hero, servicios…) y es lo
 * que usa la plantilla para pintarla; `datos` lleva lo que varía en número
 * (tarjetas, viñetas, estadísticas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sitio_bloques', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pagina_id')->constrained('sitio_paginas')->cascadeOnDelete();

            $table->string('clave', 60);
            $table->string('tipo', 30)->default('texto');

            // El "eyebrow": la línea pequeña sobre el titular.
            $table->string('antetitulo')->nullable();
            $table->string('titulo')->nullable();
            $table->string('subtitulo')->nullable();
            $table->text('texto')->nullable();
            $table->string('imagen')->nullable();

            // Listas de longitud variable (tarjetas, chips, estadísticas).
            $table->json('datos')->nullable();

            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);

            $table->timestamps();

            // Una sección por clave y página: evita duplicar el hero al guardar
            // dos veces desde el formulario.
            $table->unique(['pagina_id', 'clave']);
            $table->index(['pagina_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sitio_bloques');
    }
};
