{{--
    Cabecera de SEO común a todas las páginas públicas.

    Estaba TODO por hacer: el diagnóstico encontró que ninguna página tenía meta
    descripción —Google inventaba el resumen que ve el comprador— y que no había
    datos estructurados, así que el buscador no reconocía a Innpro como negocio
    local con dirección, teléfono y servicios.

    Espera $pagina (App\Models\SitioPagina).
--}}
@php
    use App\Support\Sitio;

    $titulo = $pagina->tituloSeo();
    $descripcion = $pagina->descripcionSeo();
    $canonica = $pagina->url();
    // El interruptor global gana: si el sitio todavía no es el oficial del
    // dominio, ninguna página debe indexarse aunque esté marcada para hacerlo.
    $noindex = Sitio::noindexGlobal() || $pagina->seo_noindex;
    $ogImagen = $pagina->seo_og_imagen ?: ($pagina->imagen ?: asset('images/logo.png'));
@endphp

<title>{{ $titulo }}</title>
<meta name="description" content="{{ $descripcion }}">
<link rel="canonical" href="{{ $canonica }}">

@if ($noindex)
    <meta name="robots" content="noindex, nofollow">
@else
    <meta name="robots" content="index, follow, max-image-preview:large">
@endif

@if ($verificacion = Sitio::verificacionSearchConsole())
    <meta name="google-site-verification" content="{{ $verificacion }}">
@endif

{{-- Cómo se ve el enlace al compartirlo por WhatsApp, que es por donde llega
     buena parte del tráfico comercial de Innpro. --}}
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ Sitio::nombre() }}">
<meta property="og:title" content="{{ $titulo }}">
<meta property="og:description" content="{{ $descripcion }}">
<meta property="og:url" content="{{ $canonica }}">
<meta property="og:image" content="{{ $ogImagen }}">
<meta property="og:locale" content="es_CO">
<meta name="twitter:card" content="summary_large_image">

<script type="application/ld+json">{!! json_encode(Sitio::jsonLdNegocio(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

@if ($pagina->tipo === \App\Models\SitioPagina::SERVICIO)
    <script type="application/ld+json">{!! json_encode(Sitio::jsonLdServicio($pagina), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endif

{{-- Analytics va al final y solo si hay ID: sin esto no hay forma de saber
     cuántas visitas llegan, de dónde, ni cuántas terminan en contacto. --}}
@if ($ga4 = Sitio::analytics())
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4 }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($ga4));

        // Contactos como eventos: la propuesta mide llamadas, WhatsApp y envíos
        // de formulario, no solo visitas.
        document.addEventListener('click', function (e) {
            var a = e.target.closest('a');
            if (!a) return;
            var href = a.getAttribute('href') || '';
            if (href.indexOf('wa.me') !== -1) gtag('event', 'contacto_whatsapp');
            else if (href.indexOf('tel:') === 0) gtag('event', 'contacto_llamada');
            else if (href.indexOf('mailto:') === 0) gtag('event', 'contacto_correo');
        });
    </script>
@endif
