<?php

namespace App\Support;

use App\Models\Parametros;
use App\Models\SitioPagina;
use Illuminate\Support\Str;

/**
 * Datos del sitio público y su SEO.
 *
 * Existe para que la plantilla no consulte la tabla `parametros` a mano en
 * quince sitios: el NAP (nombre, dirección, teléfono) tiene que salir IDÉNTICO
 * en la página, en los datos estructurados y en el perfil de Google Business, y
 * la única forma de garantizarlo es que salga de una sola función.
 */
class Sitio
{
    /**
     * Todos los parámetros del sitio, leídos de una sola vez.
     *
     * La portada pide una docena de valores (NAP, redes, analytics) y cada uno
     * era una consulta; se cargan juntos en una. Es memoria por PROCESO, lo que
     * en PHP-FPM equivale a por petición porque el proceso muere al terminarla.
     *
     * `olvidar()` existe para lo que NO cumple esa premisa: las pruebas, que
     * corren muchas peticiones en el mismo proceso, y cualquier worker de cola
     * de larga vida. Sin él, un cambio de ajuste no se vería hasta reiniciar.
     */
    private static ?array $valores = null;

    public static function valor(string $clave, string $default = ''): string
    {
        self::$valores ??= Parametros::where('nombre_parametro', 'like', 'sitio\_%')
            ->pluck('valor_parametro', 'nombre_parametro')
            ->all();

        $valor = self::$valores[$clave] ?? null;

        return ($valor === null || $valor === '') ? $default : (string) $valor;
    }

    /** Vacía la memoria. Llamar tras editar los ajustes o entre pruebas. */
    public static function olvidar(): void
    {
        self::$valores = null;
    }

    public static function nombre(): string
    {
        return self::valor('sitio_nombre', 'Innpro Ingeniería SAS');
    }

    public static function ciudad(): string
    {
        return self::valor('sitio_ciudad', 'Bogotá');
    }

    public static function direccion(): string
    {
        return self::valor('sitio_direccion');
    }

    public static function telefono(): string
    {
        return self::valor('sitio_telefono');
    }

    public static function celular(): string
    {
        return self::valor('sitio_celular');
    }

    public static function email(): string
    {
        return self::valor('sitio_email');
    }

    /** Solo dígitos: es lo que exige el enlace wa.me. */
    public static function whatsapp(): string
    {
        return preg_replace('/\D/', '', self::valor('sitio_whatsapp'));
    }

    public static function enlaceWhatsapp(?string $mensaje = null): string
    {
        $numero = self::whatsapp();

        if ($numero === '') {
            return '';
        }

        $texto = $mensaje ?: 'Hola, quisiera información sobre sus servicios de seguridad electrónica.';

        return 'https://wa.me/'.$numero.'?text='.rawurlencode($texto);
    }

    /** @return array<int,string> Perfiles sociales, sin los vacíos. */
    public static function redes(): array
    {
        return array_values(array_filter([
            self::valor('sitio_facebook'),
            self::valor('sitio_linkedin'),
            self::valor('sitio_instagram'),
        ]));
    }

    /**
     * El anio en que Innpro empezo a operar. Es la UNICA fuente del dato.
     *
     * El cliente reclamo que los anios de experiencia no cuadran, y tenia
     * razon: el WordPress dice «13 anos» en la portada y «mas de 9 anos» en
     * Nuestra Empresa, y el sitio nuevo heredo el 13. La causa de fondo es que
     * el numero estaba ESCRITO como texto en cuatro sitios distintos, asi que
     * envejece solo y hay que acordarse de subirlo cada enero en todos.
     *
     * Guardando el anio de inicio en vez del total, el numero se calcula y no
     * vuelve a quedarse viejo nunca.
     */
    public static function anioFundacion(): int
    {
        return (int) self::valor('sitio_anio_fundacion', '2011');
    }

    public static function aniosExperiencia(): int
    {
        return max(1, (int) date('Y') - self::anioFundacion());
    }

    /**
     * Resuelve los marcadores de un texto del panel antes de pintarlo.
     *
     * Se aplica al PINTAR y no en un accesor del modelo a proposito: el panel
     * edita esos mismos campos, y si el accesor devolviera el numero ya
     * resuelto, el formulario mostraria «15» y al guardar se grabaria el 15
     * fijo — o sea que el marcador se destruiria solo la primera vez que
     * alguien tocara el texto, que es justo el problema que viene a resolver.
     *
     * Los del NAP existen por lo mismo: la politica de privacidad tiene que
     * nombrar la razon social, el NIT, la direccion y el correo, y si se
     * escriben dentro del texto legal se quedan viejos el dia que la empresa se
     * mude — con el agravante de que ahi el dato desactualizado es el del
     * responsable del tratamiento de datos.
     *
     * Marcadores: {anios}, {ciudad}, {empresa}, {nit}, {direccion}, {email},
     * {telefono}.
     */
    public static function txt(?string $texto): string
    {
        return strtr((string) $texto, [
            '{anios}' => (string) self::aniosExperiencia(),
            '{ciudad}' => self::ciudad(),
            '{empresa}' => self::nombre(),
            '{nit}' => self::valor('sitio_nit', '(NIT por configurar en Sitio web → Ajustes)'),
            '{direccion}' => self::direccion(),
            '{email}' => self::email(),
            '{telefono}' => self::celular() ?: self::telefono(),
        ]);
    }

    public static function analytics(): string
    {
        return self::valor('sitio_ga4');
    }

    public static function verificacionSearchConsole(): string
    {
        return self::valor('sitio_search_console');
    }

    /**
     * Interruptor de pánico para el buscador.
     *
     * Mientras este sitio NO sea el oficial del dominio, dejarlo indexable lo
     * pone a competir con el WordPress por las mismas palabras: dos páginas
     * propias peleando entre sí posicionan peor que una sola.
     */
    public static function noindexGlobal(): bool
    {
        return self::valor('sitio_noindex', '0') === '1';
    }

    /**
     * Datos estructurados del negocio local.
     *
     * Es lo que hace que Google entienda que Innpro es una empresa con
     * dirección, teléfono y servicios en Bogotá, y no un sitio cualquiera. Sin
     * esto no puede mostrarla como negocio local, que para quien instala en
     * sitio suele traer más contactos que el sitio mismo.
     */
    public static function jsonLdNegocio(): array
    {
        $datos = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => self::nombre(),
            'url' => url('/'),
            'image' => asset('images/logo.png'),
            'description' => 'Consultoría e integración en seguridad electrónica: CCTV, control de acceso, detección de incendios y control perimetral en '.self::ciudad().'.',
        ];

        if (self::direccion() !== '') {
            $datos['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => self::direccion(),
                'addressLocality' => self::ciudad(),
                'addressCountry' => 'CO',
            ];
        }

        // El teléfono va en `telephone` y los demás en `contactPoint`: Google
        // muestra el primero en el resultado, así que ahí va el que se quiere
        // que suene.
        $telefonos = array_values(array_filter([self::celular(), self::telefono()]));
        if ($telefonos) {
            $datos['telephone'] = $telefonos[0];
        }

        if (self::email() !== '') {
            $datos['email'] = self::email();
        }

        if ($cobertura = self::valor('sitio_cobertura')) {
            $datos['areaServed'] = $cobertura;
        }

        if ($horario = self::valor('sitio_horario')) {
            $datos['openingHours'] = $horario;
        }

        if ($redes = self::redes()) {
            $datos['sameAs'] = $redes;
        }

        return $datos;
    }

    /**
     * Datos estructurados de un servicio concreto, para las páginas del frente
     * que compite por búsquedas de compra.
     */
    public static function jsonLdServicio(SitioPagina $pagina): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $pagina->titulo,
            'description' => $pagina->descripcionSeo(),
            'url' => $pagina->url(),
            'areaServed' => ['@type' => 'City', 'name' => self::ciudad()],
            'provider' => [
                '@type' => 'LocalBusiness',
                'name' => self::nombre(),
                'url' => url('/'),
            ],
        ];
    }

    /** Los servicios publicados, para el menú y el pie de página. */
    public static function menuServicios()
    {
        return SitioPagina::publicadas()
            ->deTipo(SitioPagina::SERVICIO)
            ->orderBy('orden')
            ->get(['id', 'slug', 'titulo', 'subtitulo', 'icono']);
    }

    /** Texto plano y recortado, para meta descripciones armadas al vuelo. */
    public static function resumir(?string $html, int $limite = 155): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $html))), $limite, '');
    }
}
