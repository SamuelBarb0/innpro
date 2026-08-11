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

@include('sitio.partials.cargador')

<!-- ══════════════ BARRA SUPERIOR ══════════════ -->
@include('sitio.partials.cabecera', ['servicios' => $servicios, 'enPortada' => true])

<!-- ══════════════ HERO ══════════════ -->
<section class="hero" id="top">

  {{-- La sala de control, de fondo. El `src` va en data-src a propósito: lo
       pone el JavaScript solo en escritorio, sin prefers-reduced-motion y sin
       ahorro de datos. Si estuviera en el src, el móvil se bajaría 1,2 MB de
       video que ni siquiera va a enseñar. El cartel (80 KB) cubre esos casos
       y además es lo que se ve mientras el video llega. --}}
  <video class="hero__video" id="heroVideo"
         muted loop playsinline preload="none" tabindex="-1" aria-hidden="true"
         poster="{{ asset('images/hero-poster.jpg') }}"
         data-src="{{ asset('videos/innpro-hero.mp4') }}"></video>
  <div class="hero__scrim"></div>

  {{-- Fondo del hero: dos orbes que respiran y un trazado de pistas que se
       dibuja solo con un pulso viajando por dentro. Todo es SVG y degradados,
       no hay ni una imagen que descargar. Se esconde en móvil y con
       prefers-reduced-motion. --}}
  <span class="orb orb--1"></span>
  <span class="orb orb--2"></span>

  <svg class="circuit" viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
    <path id="t1" style="--len:900;--t:0"   d="M-20 706 H180 L240 646 H420 L470 696 H700"/>
    <path id="t2" style="--len:800;--t:.5"  d="M1460 178 H1280 L1220 238 H1040 L990 188 H820"/>
    <path id="t3" style="--len:400;--t:1"   d="M118 920 V800 L178 740 V596"/>
    <path id="t4" style="--len:450;--t:1.5" d="M1338 920 V762 L1278 702 V556"/>

    <circle r="2.6" style="--t:0">
      <animateMotion dur="9s" begin="2.2s" repeatCount="indefinite"><mpath href="#t1"/></animateMotion>
    </circle>
    <circle r="2.6" style="--t:.5">
      <animateMotion dur="11s" begin="3s" repeatCount="indefinite"><mpath href="#t2"/></animateMotion>
    </circle>
    <circle r="2.2" style="--t:1">
      <animateMotion dur="7s" begin="3.6s" repeatCount="indefinite"><mpath href="#t3"/></animateMotion>
    </circle>
    <circle r="2.2" style="--t:1.5">
      <animateMotion dur="8s" begin="4.2s" repeatCount="indefinite"><mpath href="#t4"/></animateMotion>
    </circle>
  </svg>

  <div class="shell">
    @php
      $hero = $pagina->bloque('hero');
      // El titular se anima palabra por palabra. Se parte aquí y no en la base
      // para que quien edite escriba una frase normal y no tenga que pensar en
      // etiquetas; la segunda mitad se resalta sola.
      $palabras = preg_split('/\s+/', trim($hero?->titulo ?: $pagina->subtitulo), -1, PREG_SPLIT_NO_EMPTY);
      $corte = (int) ceil(count($palabras) / 2);
      $ctaUno = $hero?->grupo('cta_principal') ?: [];
      $ctaDos = $hero?->grupo('cta_secundario') ?: [];
    @endphp

    <div>
      @if ($hero?->antetitulo)
        <div class="eyebrow">{{ $hero->antetitulo }}</div>
      @endif

      <h1 class="hero__title">
        @foreach ($palabras as $i => $palabra)
          <span class="w"><i style="--i:{{ $i }}">@if ($i >= $corte)<em>{{ $palabra }}</em>@else{{ $palabra }}@endif</i></span>@if ($i + 1 === $corte)<br>@endif
        @endforeach
      </h1>

      @if ($hero?->texto)
        <p class="hero__sub">{{ $hero->texto }}</p>
      @endif

      <div class="hero__cta">
        @if (filled($ctaUno['texto'] ?? null))
          <a href="{{ $ctaUno['url'] ?? '#contacto' }}" class="btn"><span>{{ $ctaUno['texto'] }}</span></a>
        @endif
        @if (filled($ctaDos['texto'] ?? null))
          <a href="{{ $ctaDos['url'] ?? '#contacto' }}" class="btn btn--ghost"><span>{{ $ctaDos['texto'] }}</span></a>
        @endif
      </div>

      <div class="hero__stats">
        @foreach ($hero?->lista('estadisticas') ?? [] as $stat)
          <div class="stat">
            <b data-count="{{ $stat['numero'] ?? 0 }}" @if (filled($stat['sufijo'] ?? null)) data-suf="{{ $stat['sufijo'] }}" @endif>0</b>
            <span>{{ $stat['etiqueta'] ?? '' }}</span>
          </div>
        @endforeach
      </div>
    </div>

    <div class="lens" id="lens">
      <div class="lens__ring"></div>
      <div class="lens__ring"></div>
      <div class="lens__ring"></div>
      {{-- Las marcas de grado van DESPUÉS de los tres anillos a propósito:
           los anillos se posicionan con :nth-child(1..3) y meter esto antes
           les correría el índice a todos. --}}
      <div class="lens__ticks"></div>
      <div class="lens__sweep"></div>
      <div class="lens__cross"><i></i><i></i></div>
      <div class="lens__iris"><span class="lens__glint"></span></div>
      <span class="node"></span><span class="node"></span><span class="node"></span>
    </div>
  </div>

  <div class="hero__scroll"><i></i>Scroll</div>
</section>

<!-- ══════════════ SERVICIOS ══════════════ -->
<section class="sect sect--alt" id="servicios">
  <div class="shell">
    @php $svc = $pagina->bloque('servicios'); @endphp

    <div class="reveal">
      @if ($svc?->antetitulo)<div class="eyebrow">{{ $svc->antetitulo }}</div>@endif
      <h2 class="h-sec">{{ $svc?->titulo ?: 'Nuestros servicios' }}</h2>
      {{-- Párrafo de entrada, opcional. Si el panel lo deja vacío no se pinta
           y las tarjetas suben, sin dejar un hueco raro. --}}
      @if ($svc?->texto)
        <p class="lead" style="margin-top:1.4rem;max-width:64ch">{{ $svc->texto }}</p>
      @endif
    </div>

    <div class="svc">
      @foreach ($svc?->lista('tarjetas') ?? [] as $i => $tarjeta)
        @php
          // Enlazar la tarjeta a su página de servicio es media pelea del SEO:
          // sin enlace interno desde la portada, Google llega tarde y con menos
          // fuerza a las páginas que tienen que posicionar.
          $destino = $tarjeta['url'] ?? '';
        @endphp
        <article class="card bracket reveal" style="--d:{{ 80 + $i * 120 }}ms">
          {{-- El brillo que sigue al cursor necesita su propio elemento: los
               pseudoelementos de la tarjeta se los quedan los corchetes. --}}
          <span class="card__glow"></span>
          @if (filled($tarjeta['destacado'] ?? null))
            <span class="card__sello">{{ $tarjeta['destacado'] }}</span>
          @endif
          <div class="card__n">{{ $tarjeta['numero'] ?? '' }}</div>
          <div class="card__ico">@include('sitio.partials.icono', ['nombre' => $tarjeta['icono'] ?? null, 'indice' => $i])</div>
          <h3>{{ $tarjeta['titulo'] ?? '' }}</h3>
          <p>{{ $tarjeta['texto'] ?? '' }}</p>

          {{-- «Qué incluye». Es lo que convierte una tarjeta de eslogan en una
               tarjeta que responde de verdad a qué compra el cliente. Se llena
               desde el panel, una cosa por línea; sin nada escrito, la tarjeta
               se queda exactamente como estaba. --}}
          @php $puntos = array_filter((array) ($tarjeta['puntos'] ?? []), 'is_string'); @endphp
          @if (! empty($puntos))
            <ul class="card__puntos">
              @foreach ($puntos as $punto)
                <li>{{ $punto }}</li>
              @endforeach
            </ul>
          @endif

          @if (filled($destino))
            <a class="card__more" href="{{ $destino }}">Ver más →</a>
          @else
            <span class="card__more">Ver más →</span>
          @endif
        </article>
      @endforeach
    </div>

    {{-- Las páginas de servicio, enlazadas desde la portada. Es el frente que
         produce el crecimiento: cada una compite por su propia búsqueda. --}}
    @if ($servicios->isNotEmpty())
      <div class="svc-links reveal" style="--d:420ms">
        @foreach ($servicios as $s)
          <a class="chip chip--link" href="{{ route('sitio.servicio', $s->slug) }}">{{ $s->titulo }}</a>
        @endforeach
      </div>
    @endif
  </div>
</section>

<!-- ══════════════ EMPRESA ══════════════ -->
<section class="sect" id="empresa">
  <div class="shell about">
    <div class="about__art reveal reveal--left">
      <span class="pulse"></span><span class="pulse"></span>
      <img src="{{ asset('images/logo.png') }}" alt="Innpro Ingeniería">
    </div>

    @php
      $emp = $pagina->bloque('empresa');
      $ctaEmp = $emp?->grupo('cta') ?: [];
    @endphp

    <div class="reveal reveal--right" style="--d:140ms">
      @if ($emp?->antetitulo)<div class="eyebrow">{{ $emp->antetitulo }}</div>@endif
      <h2 class="h-sec">{{ $emp?->titulo }}</h2>
      <p class="lead" style="margin-top:1.6rem">{{ $emp?->texto }}</p>
      @if (filled($ctaEmp['texto'] ?? null))
        <div class="hero__cta" style="opacity:1;animation:none;margin-top:2.2rem">
          <a href="{{ $ctaEmp['url'] ?? '#contacto' }}" class="btn"><span>{{ $ctaEmp['texto'] }}</span></a>
        </div>
      @endif
    </div>
  </div>
</section>

<!-- ══════════════ LINEAMIENTOS ══════════════ -->
<section class="sect sect--alt">
  <div class="shell">
    @php $lin = $pagina->bloque('lineamientos'); @endphp

    <div class="reveal">
      @if ($lin?->antetitulo)<div class="eyebrow">{{ $lin->antetitulo }}</div>@endif
      <h2 class="h-sec">{{ $lin?->titulo }}</h2>
    </div>

    <div class="guide">
      @foreach ($lin?->lista('puntos') ?? [] as $i => $punto)
        {{-- El número se calcula: quien agregue un lineamiento no tiene que
             acordarse de renumerar los demás. --}}
        <div class="guide__i reveal" style="--d:{{ 60 + $i * 90 }}ms">
          <b>{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</b>
          <p>{{ $punto['texto'] ?? '' }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>

<!-- ══════════════ EXPERIENCIA ══════════════ -->
<section class="sect" id="experiencia">
  <div class="shell">
    <div class="exp">
      @php $exp = $pagina->bloque('experiencia'); @endphp

      <div class="reveal">
        @if ($exp?->antetitulo)<div class="eyebrow">{{ $exp->antetitulo }}</div>@endif
        <h2 class="h-sec">{{ $exp?->titulo }}</h2>
      </div>

      <div class="reveal reveal--right" style="--d:140ms">
        {{-- Los saltos de línea del editor se vuelven párrafos: quien escribe
             no tiene por qué saber HTML para separar dos ideas. --}}
        @foreach (preg_split('/\n\s*\n/', trim((string) $exp?->texto), -1, PREG_SPLIT_NO_EMPTY) as $i => $parrafo)
          <p class="lead" @if ($i > 0) style="margin-top:1.2rem" @endif>{{ trim($parrafo) }}</p>
        @endforeach

        <div class="chips">
          @foreach ($exp?->lista('chips') ?? [] as $chip)
            <span class="chip">{{ $chip['texto'] ?? '' }}</span>
          @endforeach
        </div>
      </div>
    </div>

    <div class="marquee">
      <div class="marquee__t">
        <span>Industrial</span><span>Residencial</span><span>Comercial</span><span>Seguridad electrónica</span><span>Ingeniería electrónica</span>
        <span>Industrial</span><span>Residencial</span><span>Comercial</span><span>Seguridad electrónica</span><span>Ingeniería electrónica</span>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════ CONTACTO ══════════════ -->
<section class="sect contact" id="contacto">
  <div class="shell">
    @php
      use App\Support\Sitio;
      $con = $pagina->bloque('contacto');
    @endphp

    <div class="reveal">
      @if ($con?->antetitulo)<div class="eyebrow">{{ $con->antetitulo }}</div>@endif
      <h2 class="h-sec">{{ $con?->titulo ?: 'Contáctenos' }}</h2>
      @if ($con?->texto)<p class="lead" style="margin-top:1.4rem;max-width:60ch">{{ $con->texto }}</p>@endif
    </div>

    {{-- Estos datos salen de `Sitio` y no escritos aquí porque tienen que ser
         IDÉNTICOS a los de los datos estructurados y a los del perfil de Google
         Business: el diagnóstico marcó como CRÍTICO que la dirección fuera
         inconsistente entre directorios, y esa incoherencia es justo lo que le
         impide a Google fiarse del negocio. --}}
    <div class="ccols">
      <div class="ccol reveal" style="--d:80ms">
        <h4>Contáctenos</h4>
        @if ($cel = Sitio::celular())
          <a href="tel:{{ preg_replace('/[^\d+]/', '', $cel) }}">Celular: {{ $cel }}</a>
        @endif
        @if ($fijo = Sitio::telefono())
          <a href="tel:{{ preg_replace('/[^\d+]/', '', $fijo) }}">Fijo: {{ $fijo }}</a>
        @endif
        @if ($mail = Sitio::email())
          <a href="mailto:{{ $mail }}">{{ $mail }}</a>
        @endif
        @if ($wa = Sitio::enlaceWhatsapp())
          <a href="{{ $wa }}" target="_blank" rel="noopener">Escríbanos por WhatsApp</a>
        @endif
      </div>
      <div class="ccol reveal" style="--d:180ms">
        <h4>Dirección</h4>
        <p>{{ Sitio::direccion() }}</p>
        <p>{{ Sitio::ciudad() }}, D.C.</p>
        <p>Colombia</p>
        @if ($horario = Sitio::valor('sitio_horario'))<p>{{ $horario }}</p>@endif
      </div>
      <div class="ccol reveal" style="--d:280ms">
        <h4>Política institucional</h4>
        <p>Siempre comprometidos en ofrecer un servicio de alta calidad, oportuno y eficaz,
           que supere las expectativas de nuestros clientes por medio de un equipo humano idóneo.</p>
      </div>
    </div>

    <div class="cta-band bracket reveal reveal--zoom" style="--d:120ms">
      <h3>¿Ya es cliente de Innpro?</h3>
      <p>Ingrese a la plataforma para consultar cotizaciones y órdenes de servicio.</p>
      <a href="{{ route('login') }}" class="btn"><span>Ingresar a la plataforma</span></a>
    </div>
  </div>
</section>

<!-- ══════════════ FOOTER ══════════════ -->
<footer class="foot">
  <div class="shell">
    <small>© {{ date('Y') }} {{ App\Support\Sitio::nombre() }} · {{ App\Support\Sitio::ciudad() }}, Colombia</small>
    <span class="soc">
      <a href="https://www.facebook.com/innproingenieria" target="_blank" rel="noopener" aria-label="Facebook"><svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H8v3h2v7h3v-7h3l1-3h-4v-2c0-.6.4-1 1-1z"/></svg></a>
      <a href="https://www.linkedin.com/company/innpro-ingenieria" target="_blank" rel="noopener" aria-label="LinkedIn"><svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M6.9 8H4v12h2.9V8zM5.4 3.5A1.7 1.7 0 103.7 5.2 1.7 1.7 0 005.4 3.5zM20 13.4c0-3.2-1.7-4.7-4-4.7a3.5 3.5 0 00-3.1 1.7V8H10v12h2.9v-6.3c0-1.7.3-3.3 2.4-3.3s2 1.9 2 3.4V20H20z"/></svg></a>
      <a href="https://www.instagram.com/innproingenieria" target="_blank" rel="noopener" aria-label="Instagram"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3.2" y="3.2" width="17.6" height="17.6" rx="5"/><circle cx="12" cy="12" r="4.1"/><circle cx="17.3" cy="6.7" r="1.15" fill="currentColor" stroke="none"/></svg></a>
    </span>
  </div>
</footer>

{{-- Botón flotante de WhatsApp: es un entregable de la propuesta y la ruta de
     contacto más corta desde móvil. Si no hay número configurado no se pinta,
     para no dejar un botón que lleve a un chat inexistente. --}}
@if ($enlaceWa = App\Support\Sitio::enlaceWhatsapp())
<a class="wa" href="{{ $enlaceWa }}" target="_blank" rel="noopener" aria-label="Escríbanos por WhatsApp">
  <svg width="27" height="27" viewBox="0 0 24 24" fill="currentColor"><path d="M17.5 14.4c-.3-.2-1.7-.9-2-1s-.5-.2-.7.1-.7 1-.9 1.2-.4.2-.7.1a8 8 0 01-2.4-1.5 9 9 0 01-1.6-2c-.2-.3 0-.5.1-.6l.5-.6a2 2 0 00.3-.5.6.6 0 000-.6L9.4 6.7c-.3-.6-.5-.5-.7-.5h-.6a1.2 1.2 0 00-.9.4A3.6 3.6 0 006 9.2a6.3 6.3 0 001.3 3.3 14.3 14.3 0 005.5 4.9 18.6 18.6 0 001.8.6 4.4 4.4 0 002-.1 3.3 3.3 0 002.2-1.5 2.7 2.7 0 00.2-1.5c-.1-.2-.3-.3-.5-.5zM12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2zm0 18.3a8.3 8.3 0 01-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.3 8.3 0 1112 20.3z"/></svg>
</a>
@endif

@include('sitio.partials.scripts')
</body>
</html>
