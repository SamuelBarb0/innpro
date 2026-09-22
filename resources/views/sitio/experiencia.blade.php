{{--
    El histórico de proyectos, filtrable por sector.

    Los proyectos salen del bloque «Casos de éxito» de la portada: una sola
    lista para las dos pantallas, porque dos listas separadas terminan siempre
    con una al día y la otra no.

    El filtro es JavaScript sobre lo que ya está en la página, no una recarga:
    son unas decenas de tarjetas, y así funciona al instante. Sin JavaScript se
    ven todas, que es exactamente lo que hay que ver.
--}}
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Experiencia y casos de éxito · {{ App\Support\Sitio::nombre() }}</title>
<meta name="description" content="Proyectos de seguridad electrónica e infraestructura tecnológica ejecutados por {{ App\Support\Sitio::nombre() }} en los sectores gobierno, comercial, propiedad horizontal, industrial y educativo. Sede principal en Bogotá con cobertura nacional.">
<link rel="canonical" href="{{ route('sitio.experiencia') }}">
@if (App\Support\Sitio::noindexGlobal())<meta name="robots" content="noindex,nofollow">@endif
<meta name="theme-color" content="#eff2f9">
<link rel="icon" href="{{ asset('images/logo.png') }}">

@include('sitio.partials.tema_head')

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=chakra-petch:400,500,600,700|sora:300,400,500,600,700&display=swap" rel="stylesheet">

@include('sitio.partials.estilos')
</head>
<body>

@include('sitio.partials.cabecera', ['servicios' => $servicios, 'enPortada' => false])

<main class="pg">
  <div class="shell">
    <nav class="pg__crumbs" aria-label="Ruta">
      <a href="{{ url('/') }}">Inicio</a> ／ Experiencia
    </nav>

    <h1 class="pg__title">{{ $bloque?->titulo ?: 'Casos de éxito y experiencia' }}</h1>

    @if ($bloque?->texto)
      <p class="pg__sub">{{ App\Support\Sitio::txt($bloque->texto) }}</p>
    @endif

    @if ($sectores->isNotEmpty())
      <div class="filtros" id="filtros" role="group" aria-label="Filtrar proyectos por sector">
        <button type="button" class="chip chip--link is-on" data-filtro="">Todos</button>
        @foreach ($sectores as $sector)
          <button type="button" class="chip chip--link" data-filtro="{{ $sector }}">{{ $sector }}</button>
        @endforeach
      </div>
    @endif

    <div class="casos casos--todos" id="casos">
      @foreach ($proyectos as $i => $caso)
        @include('sitio.partials.caso', ['caso' => $caso, 'i' => $i % 4])
      @endforeach
    </div>

    @if ($proyectos->isEmpty())
      <p class="lead">Estamos preparando esta sección. Escríbanos y le contamos qué proyectos hemos ejecutado en su sector.</p>
    @endif

    <div class="hero__cta" style="opacity:1;animation:none;margin-top:3rem">
      <a href="{{ url('/#contacto') }}" class="btn"><span>Cuéntenos su proyecto</span></a>
    </div>
  </div>
</main>

<footer class="foot">
  <div class="shell">
    <small>© {{ date('Y') }} {{ App\Support\Sitio::nombre() }} · {{ App\Support\Sitio::ciudad() }}, Colombia</small>
    <span class="soc">
      @foreach (App\Support\Sitio::redes() as $url)
        <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="Perfil social">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/></svg>
        </a>
      @endforeach
    </span>
    @include('sitio.partials.legales')
  </div>
</footer>

@if ($enlaceWa = App\Support\Sitio::enlaceWhatsapp('Hola, vi sus casos de éxito y quiero cotizar un proyecto'))
<a class="wa" href="{{ $enlaceWa }}" target="_blank" rel="noopener" aria-label="Escríbanos por WhatsApp">
  <svg width="27" height="27" viewBox="0 0 24 24" fill="currentColor"><path d="M17.5 14.4c-.3-.2-1.7-.9-2-1s-.5-.2-.7.1-.7 1-.9 1.2-.4.2-.7.1a8 8 0 01-2.4-1.5 9 9 0 01-1.6-2c-.2-.3 0-.5.1-.6l.5-.6a2 2 0 00.3-.5.6.6 0 000-.6L9.4 6.7c-.3-.6-.5-.5-.7-.5h-.6a1.2 1.2 0 00-.9.4A3.6 3.6 0 006 9.2a6.3 6.3 0 001.3 3.3 14.3 14.3 0 005.5 4.9 18.6 18.6 0 001.8.6 4.4 4.4 0 002-.1 3.3 3.3 0 002.2-1.5 2.7 2.7 0 00.2-1.5c-.1-.2-.3-.3-.5-.5zM12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2zm0 18.3a8.3 8.3 0 01-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.3 8.3 0 1112 20.3z"/></svg>
</a>
@endif

<script>
  // Filtro por sector: enseña u oculta las tarjetas que ya están en la página.
  (function () {
    var filtros = document.getElementById('filtros');
    var casos = document.getElementById('casos');
    if (!filtros || !casos) return;

    filtros.addEventListener('click', function (e) {
      var boton = e.target.closest('[data-filtro]');
      if (!boton) return;

      var sector = boton.dataset.filtro;
      filtros.querySelectorAll('[data-filtro]').forEach(function (b) {
        b.classList.toggle('is-on', b === boton);
      });
      casos.querySelectorAll('.caso').forEach(function (c) {
        c.hidden = sector !== '' && c.dataset.sector !== sector;
      });
    });
  })();
</script>

@include('sitio.partials.cookies')
@include('sitio.partials.scripts')
</body>
</html>
