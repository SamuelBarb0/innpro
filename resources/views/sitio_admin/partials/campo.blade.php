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
@else
  <input type="text" class="form-control form-control-sm" id="{{ $id }}" name="{{ $nombre }}"
         value="{{ $mostrar }}">
@endif

@if (! empty($def['ayuda']))
  <div class="form-text small">{{ $def['ayuda'] }}</div>
@endif
