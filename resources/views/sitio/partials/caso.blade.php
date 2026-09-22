{{--
    Un caso de éxito: cliente, sector y qué se implementó. La usan la portada
    (los cuatro primeros) y la página de Experiencia (todos, filtrables).

    La foto es opcional a propósito. El cliente pidió fotos reales de montaje y
    todavía no las ha entregado, así que la tarjeta tiene que sostenerse sin
    ellas: sin foto se dibuja el sector sobre un degradado técnico, que es
    mejor que un hueco gris o una imagen de banco que no es suya.

    - `caso`  la fila de `datos` (cliente, sector, solucion, imagen).
    - `i`     la posición, para el retardo de la animación.
--}}
<article class="caso bracket reveal" style="--d:{{ 80 + $i * 110 }}ms" @if (filled($caso['sector'] ?? null)) data-sector="{{ $caso['sector'] }}" @endif>
  <div class="caso__foto">
    @if (filled($caso['imagen'] ?? null))
      <img src="{{ $caso['imagen'] }}" alt="Proyecto de {{ $caso['cliente'] ?? '' }}" loading="lazy" decoding="async">
    @else
      {{-- Sin foto: un icono discreto. El sector ya sale debajo, y repetirlo
           aquí era decir dos veces lo mismo en la misma tarjeta. --}}
      <span class="caso__marca">@include('sitio.partials.icono', ['nombre' => 'escudo', 'indice' => $i])</span>
    @endif
  </div>
  <div class="caso__b">
    @if (filled($caso['sector'] ?? null))
      <div class="caso__sector">{{ $caso['sector'] }}</div>
    @endif
    <h3>{{ $caso['cliente'] ?? '' }}</h3>
    @if (filled($caso['solucion'] ?? null))
      <p>{{ $caso['solucion'] }}</p>
    @endif
  </div>
</article>
