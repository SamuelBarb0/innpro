{{--
    Un campo del formulario, dibujado a partir del esquema.

    Espera:
      $nombre  nombre del input (ya con su ruta: datos[tarjetas][0][titulo])
      $def     definición del campo en PortadaEsquema
      $valor   valor actual

    El tipo `lineas` es un textarea que representa una lista: una cosa por
    línea. Es la forma más llevadera de editar una lista corta sin montar otro
    editor dentro del editor —quien rellena esto escribe viñetas, no JSON—.
--}}
@php
    $tipo    = $def['tipo'] ?? 'text';
    $filas   = $def['filas'] ?? 3;
    $id      = 'c'.substr(md5($nombre), 0, 8);
    $mostrar = $tipo === 'lineas' && is_array($valor) ? implode("\n", $valor) : $valor;
@endphp

<label class="form-label small fw-semibold mb-1" for="{{ $id }}">{{ $def['etiqueta'] }}</label>

@if ($tipo === 'select')
  <select class="form-select form-select-sm" id="{{ $id }}" name="{{ $nombre }}">
    @foreach ($def['opciones'] ?? [] as $valorOpcion => $texto)
      <option value="{{ $valorOpcion }}" @selected((string) $mostrar === (string) $valorOpcion)>{{ $texto }}</option>
    @endforeach
  </select>
@elseif ($tipo === 'textarea' || $tipo === 'lineas')
  <textarea class="form-control form-control-sm" id="{{ $id }}" name="{{ $nombre }}"
            rows="{{ $filas }}">{{ $mostrar }}</textarea>
@elseif ($tipo === 'imagen')
  {{-- El archivo viaja en «…[campo__archivo]» y no en el mismo nombre que la
       dirección: si compartieran nombre, el texto pisaría al archivo. El
       sufijo va DENTRO del último corchete; pegado por fuera, PHP no
       reconoce el nombre y el archivo se pierde. La
       dirección se queda a la vista para poder quitar la foto (vaciándola) o
       pegar una que ya esté en internet. --}}
  <div class="d-flex gap-2 align-items-start">
    @if (filled($mostrar))
      <img src="{{ $mostrar }}" alt="" class="rounded border flex-shrink-0"
           style="width:72px;height:54px;object-fit:cover">
    @endif
    <div class="flex-grow-1">
      <input type="file" class="form-control form-control-sm mb-1" id="{{ $id }}"
             name="{{ str_ends_with($nombre, ']') ? substr($nombre, 0, -1).'__archivo]' : $nombre.'__archivo' }}" accept="image/jpeg,image/png,image/webp">
      <input type="text" class="form-control form-control-sm" name="{{ $nombre }}"
             value="{{ $mostrar }}" placeholder="…o pega la dirección de una foto"
             aria-label="Dirección de la foto">
    </div>
  </div>
@else
  <input type="text" class="form-control form-control-sm" id="{{ $id }}" name="{{ $nombre }}"
         value="{{ $mostrar }}">
@endif

@if (! empty($def['ayuda']))
  <div class="form-text small">{{ $def['ayuda'] }}</div>
@endif
