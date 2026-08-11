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
</head>
<body>

@include('sitio.partials.cabecera', ['servicios' => $servicios, 'enPortada' => false])

<main class="pg">
  <div class="shell">
    {{-- Las migas no son decoración: le dicen a Google la jerarquía del sitio y
         le dan al visitante la salida hacia la portada, que es donde está el
         resto de la oferta. --}}
    <nav class="pg__crumbs" aria-label="Ruta">
      <a href="{{ url('/') }}">Inicio</a> ／ {{ $pagina->titulo }}
    </nav>

    {{-- Un solo H1 por página, y es el título del servicio: la jerarquía de
         encabezados estaba rota en todo el sitio viejo. --}}
    <h1 class="pg__title">{{ $pagina->titulo }}</h1>

    @if ($pagina->subtitulo)
      <p class="pg__sub">{{ $pagina->subtitulo }}</p>
    @endif

    <div class="pg__body">
      @if ($pagina->resumen)
        <p class="lead">{{ $pagina->resumen }}</p>
      @endif

      {{-- El contenido se guarda como HTML desde el panel. Va sin escapar a
           propósito para que los <h2> y las listas funcionen; por eso el
           formulario solo lo edita quien tenga rol admin. --}}
      {!! $pagina->contenido !!}
    </div>

    <div class="pg__cta">
      @if ($wa = App\Support\Sitio::enlaceWhatsapp('Hola, me interesa: '.$pagina->titulo))
        <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn"><span>Cotizar por WhatsApp</span></a>
      @endif
      @if ($cel = App\Support\Sitio::celular())
        <a href="tel:{{ preg_replace('/[^\d+]/', '', $cel) }}" class="btn btn--ghost"><span>Llamar {{ $cel }}</span></a>
      @endif
    </div>

    {{-- Enlazado entre servicios: reparte la fuerza y le da al visitante el
         siguiente paso natural cuando la página que abrió no era la suya. --}}
    @php $otros = $servicios->where('id', '!=', $pagina->id); @endphp
    @if ($otros->isNotEmpty())
      <div class="pg__otros">
        <h3>Otros servicios</h3>
        <div class="svc-links" style="justify-content:flex-start">
          @foreach ($otros as $s)
            <a class="chip chip--link" href="{{ route('sitio.servicio', $s->slug) }}">{{ $s->titulo }}</a>
          @endforeach
        </div>
      </div>
    @endif
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
  </div>
</footer>

@if ($enlaceWa = App\Support\Sitio::enlaceWhatsapp('Hola, me interesa: '.$pagina->titulo))
<a class="wa" href="{{ $enlaceWa }}" target="_blank" rel="noopener" aria-label="Escríbanos por WhatsApp">
  <svg width="27" height="27" viewBox="0 0 24 24" fill="currentColor"><path d="M17.5 14.4c-.3-.2-1.7-.9-2-1s-.5-.2-.7.1-.7 1-.9 1.2-.4.2-.7.1a8 8 0 01-2.4-1.5 9 9 0 01-1.6-2c-.2-.3 0-.5.1-.6l.5-.6a2 2 0 00.3-.5.6.6 0 000-.6L9.4 6.7c-.3-.6-.5-.5-.7-.5h-.6a1.2 1.2 0 00-.9.4A3.6 3.6 0 006 9.2a6.3 6.3 0 001.3 3.3 14.3 14.3 0 005.5 4.9 18.6 18.6 0 001.8.6 4.4 4.4 0 002-.1 3.3 3.3 0 002.2-1.5 2.7 2.7 0 00.2-1.5c-.1-.2-.3-.3-.5-.5zM12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2zm0 18.3a8.3 8.3 0 01-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.3 8.3 0 1112 20.3z"/></svg>
</a>
@endif

{{-- El mismo comportamiento de la portada. Los bloques que aquí no tienen a
     quién aplicarse (lente, contadores, tarjetas) se saltan solos. --}}
@include('sitio.partials.scripts')
</body>
</html>
