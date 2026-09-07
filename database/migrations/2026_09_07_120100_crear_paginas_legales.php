<?php

use App\Models\SitioPagina;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Política de privacidad y política de cookies.
 *
 * El sitio no tenía ninguna página legal: ni política de privacidad, ni de
 * cookies, ni un enlace en el pie. Para un sitio que recoge datos de contacto y
 * que va a medir con Analytics, eso no es un detalle de forma — la Ley 1581 de
 * 2012 obliga a informar quién trata los datos, para qué y cómo se ejercen los
 * derechos del titular.
 *
 * Van como contenido en la base y no como Blade fijo para que Innpro pueda
 * corregir el texto sin depender del proveedor, que es el mismo criterio del
 * resto del sitio. Los datos del responsable se dejan como marcadores
 * ({empresa}, {nit}, {direccion}…) para que salgan de los ajustes del NAP: si
 * la empresa se muda, el texto legal se actualiza solo.
 *
 * OJO: el NIT no está en ningún sitio público de Innpro, así que queda por
 * configurar en *Sitio web → Ajustes*. Mientras esté vacío, la página lo dice
 * en su propio texto, a propósito: es más fácil de detectar que un hueco mudo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $paginas = [
            [
                'slug' => 'politica-de-privacidad',
                'titulo' => 'Política de tratamiento de datos personales',
                'subtitulo' => 'Cómo recogemos, usamos y protegemos su información.',
                'seo_titulo' => 'Política de privacidad | {empresa}',
                'seo_descripcion' => 'Política de tratamiento de datos personales de {empresa}, conforme a la Ley 1581 de 2012 y el Decreto 1377 de 2013.',
                'orden' => 1,
                'contenido' => $this->privacidad(),
            ],
            [
                'slug' => 'politica-de-cookies',
                'titulo' => 'Política de cookies',
                'subtitulo' => 'Qué cookies usa este sitio y cómo puede desactivarlas.',
                'seo_titulo' => 'Política de cookies | {empresa}',
                'seo_descripcion' => 'Qué cookies utiliza el sitio de {empresa}, para qué sirven y cómo cambiar su elección en cualquier momento.',
                'orden' => 2,
                'contenido' => $this->cookies(),
            ],
        ];

        foreach ($paginas as $p) {
            // updateOrInsert y no insert: si la migración se vuelve a correr
            // sobre una base que ya las tiene, no se duplican las páginas ni se
            // machaca el texto que Innpro haya ajustado desde el panel.
            $existe = DB::table('sitio_paginas')
                ->where('tipo', SitioPagina::LEGAL)
                ->where('slug', $p['slug'])
                ->exists();

            if ($existe) {
                continue;
            }

            DB::table('sitio_paginas')->insert(array_merge($p, [
                'tipo' => SitioPagina::LEGAL,
                'activo' => true,
                // Sin `noindex`: son páginas que Google espera encontrar en un
                // sitio serio, y su baja prioridad en el sitemap ya evita que
                // compitan con las de servicio.
                'seo_noindex' => false,
                'publicado_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        DB::table('sitio_paginas')
            ->where('tipo', SitioPagina::LEGAL)
            ->whereIn('slug', ['politica-de-privacidad', 'politica-de-cookies'])
            ->delete();
    }

    private function privacidad(): string
    {
        return <<<'HTML'
<p>{empresa}, identificada con NIT {nit} y domicilio en {direccion}, {ciudad} (Colombia), es responsable del tratamiento de los datos personales que usted nos entrega a través de este sitio web, de WhatsApp, del correo electrónico o de cualquier otro canal de contacto.</p>

<p>Esta política se adopta en cumplimiento de la Ley 1581 de 2012, del Decreto 1377 de 2013 y demás normas que los modifiquen o reglamenten.</p>

<h2>1. Qué datos recogemos</h2>
<ul>
  <li><strong>Datos de contacto</strong> que usted nos facilita voluntariamente: nombre, empresa, teléfono, correo electrónico y la descripción de la necesidad por la que nos escribe.</li>
  <li><strong>Datos de la relación comercial</strong>, si llega a ser cliente: dirección de las sedes donde se presta el servicio, personas de contacto, cotizaciones y órdenes de servicio.</li>
  <li><strong>Datos técnicos de navegación</strong>, si usted acepta las cookies de medición: páginas visitadas, origen de la visita y tipo de dispositivo. Esta información se trata de forma agregada y no se usa para identificarle. Ver la <a href="/politica-de-cookies">política de cookies</a>.</li>
</ul>

<h2>2. Para qué los usamos</h2>
<ul>
  <li>Responder sus solicitudes de información y elaborar cotizaciones.</li>
  <li>Prestar, facturar y hacer seguimiento a los servicios contratados, incluidas las órdenes de servicio técnico y su historial.</li>
  <li>Cumplir obligaciones legales, contables y contractuales.</li>
  <li>Enviarle información sobre nuestros servicios, únicamente si usted lo ha autorizado.</li>
</ul>

<p>No vendemos, arrendamos ni cedemos sus datos personales a terceros con fines comerciales. Solo los compartimos con proveedores que nos prestan servicios de alojamiento, correo o medición, que actúan como encargados del tratamiento y están obligados a la misma confidencialidad, y con las autoridades cuando la ley lo exija.</p>

<h2>3. Sus derechos como titular</h2>
<p>De acuerdo con el artículo 8 de la Ley 1581 de 2012, usted puede en cualquier momento:</p>
<ul>
  <li>Conocer, actualizar y rectificar sus datos personales.</li>
  <li>Solicitar prueba de la autorización que otorgó, salvo cuando la ley no la exija.</li>
  <li>Ser informado sobre el uso que se ha dado a sus datos.</li>
  <li>Presentar quejas ante la Superintendencia de Industria y Comercio por infracciones a la ley.</li>
  <li>Revocar la autorización y solicitar la supresión de sus datos, cuando no exista un deber legal o contractual que lo impida.</li>
  <li>Acceder de forma gratuita a sus datos personales.</li>
</ul>

<h2>4. Cómo ejercerlos</h2>
<p>Escríbanos a <a href="mailto:{email}">{email}</a> o llámenos al {telefono}, indicando su nombre, el dato que desea consultar o corregir y un canal de respuesta. Atendemos las consultas en un plazo máximo de diez (10) días hábiles y los reclamos en quince (15) días hábiles, prorrogables en los términos que fija la ley.</p>

<h2>5. Seguridad y conservación</h2>
<p>Aplicamos medidas técnicas y administrativas razonables para evitar el acceso no autorizado, la pérdida o la alteración de la información. Los datos se conservan mientras dure la relación comercial y, después, durante el tiempo que exijan las obligaciones legales aplicables.</p>

<h2>6. Vigencia y cambios</h2>
<p>Esta política rige desde su publicación. Si la modificamos, publicaremos la versión actualizada en esta misma dirección; le recomendamos consultarla periódicamente. La fecha de la última actualización aparece al final de la página.</p>
HTML;
    }

    private function cookies(): string
    {
        return <<<'HTML'
<p>Una cookie es un archivo pequeño que un sitio web guarda en su navegador. Este sitio usa las mínimas necesarias, y ninguna de medición sin que usted lo autorice antes.</p>

<h2>1. Cookies necesarias</h2>
<p>Permiten que el sitio funcione: mantienen su sesión si entra a la plataforma, recuerdan si eligió el tema claro u oscuro y protegen los formularios frente a envíos fraudulentos. No se pueden desactivar, porque sin ellas el sitio no opera, y no se usan para seguirle el rastro.</p>

<h2>2. Cookies de medición</h2>
<p>Usamos Google Analytics 4 para saber cuántas personas visitan el sitio, desde dónde llegan y qué páginas les resultan útiles. Es información agregada: nos dice que veinte personas consultaron la página de CCTV, no quién lo hizo.</p>

<p><strong>Estas cookies solo se activan si usted las acepta.</strong> Mientras no lo haga —o si las rechaza— Analytics no guarda cookies ni identificadores en su navegador.</p>

<h2>3. Cómo cambiar su elección</h2>
<p>Puede cambiarla cuando quiera desde el enlace <em>Preferencias de cookies</em> del pie de página. También puede borrar las cookies ya guardadas o bloquearlas por completo desde la configuración de su navegador; tenga en cuenta que bloquear las necesarias puede impedir el acceso a la plataforma de clientes.</p>

<h2>4. Cookies de terceros</h2>
<p>Al pulsar el botón de WhatsApp se abre un servicio de un tercero (Meta), con sus propias condiciones y cookies, que no controlamos. Lo mismo ocurre si visita nuestros perfiles en redes sociales.</p>

<p>El tratamiento de los datos que se recogen a través de estas cookies se rige por nuestra <a href="/politica-de-privacidad">política de tratamiento de datos personales</a>.</p>
HTML;
    }
};
