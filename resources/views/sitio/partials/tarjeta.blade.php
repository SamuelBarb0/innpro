{{--
    Una tarjeta de la portada. La usan Servicios, Acompañamiento técnico y
    Misión/visión/valores: las tres son la misma pieza con distinto contenido, y
    tenerla una sola vez evita que el brillo, los corchetes o el «Ver más» se
    arreglen en una sección y se queden rotos en las otras.

    - `tarjeta`   la fila de `datos` (numero, icono, destacado, titulo, texto,
                  puntos, cta, url).
    - `i`         la posición, para el retardo de la animación y el icono por
                  defecto.
    - `conIcono`  false en las tarjetas que no representan un servicio.
--}}
@php
  // Enlazar la tarjeta a su página de servicio es media pelea del SEO: sin
  // enlace interno desde la portada, Google llega tarde y con menos fuerza a
  // las páginas que tienen que posicionar.
  $destino = $tarjeta['url'] ?? '';
  $textoCta = filled($tarjeta['cta'] ?? null) ? $tarjeta['cta'] : 'Ver más';
  $conIcono = $conIcono ?? true;
@endphp
<article class="card bracket reveal" style="--d:{{ 80 + $i * 120 }}ms">
  {{-- El brillo que sigue al cursor necesita su propio elemento: los
       pseudoelementos de la tarjeta se los quedan los corchetes. --}}
  <span class="card__glow"></span>
  @if (filled($tarjeta['destacado'] ?? null))
    <span class="card__sello">{{ $tarjeta['destacado'] }}</span>
  @endif
  @if (filled($tarjeta['numero'] ?? null))
    <div class="card__n">{{ $tarjeta['numero'] }}</div>
  @endif
  @if ($conIcono)
    <div class="card__ico">@include('sitio.partials.icono', ['nombre' => $tarjeta['icono'] ?? null, 'indice' => $i])</div>
  @endif
  <h3>{{ $tarjeta['titulo'] ?? '' }}</h3>
  @if (filled($tarjeta['texto'] ?? null))
    <p>{{ \App\Support\Sitio::txt($tarjeta['texto']) }}</p>
  @endif

  {{-- «Qué incluye». Es lo que convierte una tarjeta de eslogan en una tarjeta
       que responde de verdad a qué compra el cliente. Se llena desde el panel,
       una cosa por línea; sin nada escrito, la tarjeta se queda como estaba. --}}
  @php $puntos = array_filter((array) ($tarjeta['puntos'] ?? []), 'is_string'); @endphp
  @if (! empty($puntos))
    <ul class="card__puntos">
      @foreach ($puntos as $punto)
        <li>{{ $punto }}</li>
      @endforeach
    </ul>
  @endif

  {{-- Sin enlace ni texto propio, una tarjeta que no es de servicio no lleva
       el «Ver más» apagado: ahí no hay nada más que ver. --}}
  @if (filled($destino))
    <a class="card__more" href="{{ $destino }}">{{ $textoCta }} →</a>
  @elseif ($conIcono)
    <span class="card__more">{{ $textoCta }} →</span>
  @endif
</article>
