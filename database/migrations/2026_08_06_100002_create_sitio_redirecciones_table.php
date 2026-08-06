<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Redirecciones 301 de las URLs del WordPress viejo.
 *
 * Al pasar el dominio a este sitio, toda URL que Google ya tenía indexada
 * (`/servicios/`, `/nuestra-empresa/`, `/noticias/...`) empieza a devolver 404.
 * Un 404 masivo no es solo una mala visita: Google reinterpreta el dominio como
 * un sitio nuevo y se pierde la antigüedad, que es lo único que no se puede
 * comprar ni acelerar.
 *
 * Se guardan en base y no en el archivo de rutas para que se puedan agregar sin
 * desplegar: las URLs viejas se van descubriendo en Search Console durante
 * semanas, no todas de golpe el día del cambio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sitio_redirecciones', function (Blueprint $table) {
            $table->id();

            // Sin el dominio y sin la barra inicial: "servicios/camaras".
            $table->string('origen')->unique();
            $table->string('destino');

            // 301 permanente (traspasa el posicionamiento) o 302 temporal.
            $table->unsignedSmallInteger('codigo')->default(301);

            $table->boolean('activo')->default(true);

            // Cuántas veces se usó y cuándo fue la última: así se ve qué URLs
            // viejas siguen vivas en Google y cuáles ya se pueden retirar.
            $table->unsignedInteger('golpes')->default(0);
            $table->timestamp('ultimo_golpe_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sitio_redirecciones');
    }
};
