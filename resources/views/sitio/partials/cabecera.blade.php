{{--
    Barra superior y menú, compartidos por la portada y las páginas de servicio.

    `enPortada` decide si los enlaces del menú son anclas (#empresa) o vuelven a
    la portada (/#empresa): desde una página de servicio, un `#empresa` a secas
    no lleva a ninguna parte.

    El menú lista las páginas de servicio publicadas. No es adorno: el
    enlazado desde el menú es lo que le dice a Google que esas páginas importan,
    y la propuesta lo pide explícitamente ("enlazado desde el menú y la página
    de servicios").
--}}
@php
    use App\Support\Sitio;
    $base = ($enPortada ?? false) ? '' : url('/');
    $redes = [
        'Facebook' => Sitio::valor('sitio_facebook'),
        'LinkedIn' => Sitio::valor('sitio_linkedin'),
        'Instagram' => Sitio::valor('sitio_instagram'),
    ];
@endphp

<div class="topbar">
  <div class="shell">
    @if ($cel = Sitio::celular())
      <span>Llámanos <b><a href="tel:{{ preg_replace('/[^\d+]/', '', $cel) }}">{{ $cel }}</a></b></span>
    @endif
    <span>{{ Sitio::direccion() }} · {{ Sitio::ciudad() }}, Colombia</span>
    <span class="soc">
      @foreach ($redes as $nombre => $url)
        @if (filled($url))
          <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $nombre }}">
            @switch($nombre)
              @case('Facebook')
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H8v3h2v7h3v-7h3l1-3h-4v-2c0-.6.4-1 1-1z"/></svg>
                @break
              @case('LinkedIn')
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M6.9 8H4v12h2.9V8zM5.4 3.5A1.7 1.7 0 103.7 5.2 1.7 1.7 0 005.4 3.5zM20 13.4c0-3.2-1.7-4.7-4-4.7a3.5 3.5 0 00-3.1 1.7V8H10v12h2.9v-6.3c0-1.7.3-3.3 2.4-3.3s2 1.9 2 3.4V20H20z"/></svg>
                @break
              @default
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3.2" y="3.2" width="17.6" height="17.6" rx="5"/><circle cx="12" cy="12" r="4.1"/><circle cx="17.3" cy="6.7" r="1.15" fill="currentColor" stroke="none"/></svg>
            @endswitch
          </a>
        @endif
      @endforeach
    </span>
  </div>
</div>

<header class="nav" id="nav">
  <div class="shell">
    <a href="{{ ($enPortada ?? false) ? '#top' : url('/') }}" class="nav__logo">
      <img src="{{ asset('images/logo.png') }}" alt="{{ Sitio::nombre() }}">
    </a>
    <button class="nav__burger" id="burger" aria-label="Menú"><span></span><span></span><span></span></button>
    <nav class="nav__links" id="links">
      <a href="{{ $base }}#empresa">Nuestra Empresa</a>

      @if ($servicios->isNotEmpty())
        <div class="nav__drop">
          <a href="{{ $base }}#servicios">Servicios</a>
          <div class="nav__menu">
            @foreach ($servicios as $s)
              <a href="{{ route('sitio.servicio', $s->slug) }}">{{ $s->titulo }}</a>
            @endforeach
          </div>
        </div>
      @else
        <a href="{{ $base }}#servicios">Servicios</a>
      @endif

      <a href="{{ $base }}#experiencia">Experiencia</a>
      <a href="{{ $base }}#contacto">Contáctenos</a>
      <a href="{{ route('login') }}" class="btn"><span>Ingresar</span></a>
    </nav>
  </div>
</header>
