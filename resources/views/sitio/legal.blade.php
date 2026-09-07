<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
@include('sitio.partials.seo', ['pagina' => $pagina])
<meta name="theme-color" content="#eff2f9">
<link rel="icon" href="{{ asset('images/logo.png') }}">

@include('sitio.partials.tema_head')

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=chakra-petch:400,500,600,700|sora:300,400,500,600,700&display=swap" rel="stylesheet">

@include('sitio.partials.estilos')

<style>
  /* Un texto legal se lee de corrido, así que manda la legibilidad: medida
     corta, interlineado ancho y encabezados que se distingan del párrafo sin
     gritar. Nada de las animaciones de entrada del resto del sitio: aquí
     estorban. */
  .legal__body{max-width:72ch;font-size:1.02rem;line-height:1.85}
  .legal__body h2{margin:2.6rem 0 .9rem;font-family:var(--ff-display,inherit);font-size:1.22rem;letter-spacing:.01em}
  .legal__body h3{margin:1.8rem 0 .6rem;font-size:1.05rem}
  .legal__body p{margin:0 0 1rem}
  .legal__body ul{margin:0 0 1.2rem;padding-left:1.3rem}
  .legal__body li{margin:.42rem 0}
  .legal__body a{text-decoration:underline;text-underline-offset:.18em}
  .legal__fecha{margin-top:2.8rem;padding-top:1.2rem;border-top:1px solid rgba(128,128,128,.28);font-size:.88rem;opacity:.75}
</style>
</head>
<body>

@include('sitio.partials.cabecera', ['servicios' => $servicios, 'enPortada' => false])

<main class="pg">
  <div class="shell">
    <nav class="pg__crumbs" aria-label="Ruta">
      <a href="{{ url('/') }}">Inicio</a> ／ {{ $pagina->titulo }}
    </nav>

    <h1 class="pg__title">{{ $pagina->titulo }}</h1>

    @if ($pagina->subtitulo)
      <p class="pg__sub">{{ $pagina->subtitulo }}</p>
    @endif

    {{-- El contenido se guarda como HTML desde el panel y pasa por los
         marcadores: así la razón social, el NIT, la dirección y el correo del
         responsable del tratamiento salen de los ajustes del NAP y no escritos
         dentro del texto legal, donde envejecerían sin que nadie los mire. --}}
    <div class="legal__body">
      {!! \App\Support\Sitio::txt($pagina->contenido) !!}

      <p class="legal__fecha">
        Última actualización:
        {{ optional($pagina->updated_at)->translatedFormat('j \d\e F \d\e Y') ?: date('Y') }}
      </p>
    </div>
  </div>
</main>

<footer class="foot">
  <div class="shell">
    <small>© {{ date('Y') }} {{ App\Support\Sitio::nombre() }} · {{ App\Support\Sitio::ciudad() }}, Colombia</small>
    @include('sitio.partials.legales')
  </div>
</footer>

@include('sitio.partials.cookies')
@include('sitio.partials.scripts')
</body>
</html>
