<?php

namespace Tests\Feature;

use App\Models\SitioPagina;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La redacción final de Innpro (16-sep-2026), tal como la dejó la migración.
 *
 * Lo que se vigila son las reglas del documento, no las frases exactas —esas
 * las puede cambiar Innpro desde el panel—: que no vuelvan las afirmaciones
 * absolutas que pidieron quitar y que cada bloque cierre con su llamado a la
 * acción.
 */
class SitioRedaccionClienteTest extends TestCase
{
    use RefreshDatabase;

    private const PROHIBIDAS = [
        'herramienta más potente', 'máxima seguridad', '100% automatizada',
        '100% exacta', 'control total', 'ininterrumpido', 'más altos estándares',
    ];

    public function test_la_portada_pinta_las_secciones_nuevas(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Interventoría, mantenimiento y soporte en Bogotá')
            ->assertSee('Programar mantenimiento')
            ->assertSee('Misión, visión y valores')
            ->assertSee('formalización de contratos de servicios activos')
            ->assertSee('Confidencialidad empresarial')
            ->assertSee('Hable con nuestro equipo')
            ->assertSee('Solicite un estudio de seguridad');
    }

    public function test_bms_perimetral_y_analitica_van_dentro_de_las_paginas_existentes(): void
    {
        $this->assertCount(4, SitioPagina::deTipo(SitioPagina::SERVICIO)->get());

        $this->get('/servicios/camaras-de-seguridad-cctv-bogota')
            ->assertOk()
            ->assertSee('Analítica de video con inteligencia artificial')
            ->assertSee('según las capacidades de la solución implementada')
            ->assertSee('Cotice su sistema de CCTV en Bogotá.');

        $this->get('/servicios/control-de-acceso-biometrico-facial-bogota')
            ->assertOk()
            ->assertSee('Control de acceso vehicular')
            ->assertSee('Registro y trazabilidad digital de los accesos')
            ->assertSee('BMS y portería virtual')
            ->assertSee('citofonía digital IP/SIP y alarmas perimetrales')
            ->assertSee('Control perimetral inteligente');
    }

    public function test_no_quedan_afirmaciones_absolutas(): void
    {
        foreach (['/', '/servicios/camaras-de-seguridad-cctv-bogota', '/servicios/control-de-acceso-biometrico-facial-bogota'] as $url) {
            $html = mb_strtolower($this->get($url)->getContent());

            foreach (self::PROHIBIDAS as $frase) {
                $this->assertStringNotContainsString($frase, $html, "«{$frase}» sigue en {$url}.");
            }
        }
    }

    public function test_la_migracion_se_deshace_sin_dejar_secciones_huerfanas(): void
    {
        // Dos pasos: encima de la redacción final está ya el manual de
        // restructuración (22-sep-2026), y deshacer uno solo desharía ese.
        $this->artisan('migrate:rollback', ['--step' => 2])->assertSuccessful();

        $portada = SitioPagina::deTipo(SitioPagina::LANDING)->with('bloques')->first();
        $this->assertNull($portada->bloque('acompanamiento'));
        $this->assertNull($portada->bloque('identidad'));
        $this->get('/')->assertOk();
    }
}
