{{--
    Colector de firmas del formato técnico.

    Parámetros:
      $orden        OrdenServicio
      $tipo         'tecnico' | 'cliente'
      $titulo       Encabezado visible del bloque
      $rol          Texto bajo la línea de firma (ej. "Técnico responsable")
      $action       URL del POST que registra la firma
      $nombrePorDefecto  Nombre prellenado en el input
      $ccPorDefecto      Documento prellenado (opcional)
      $puedeQuitar  bool — muestra el botón de eliminar firma
      $urlQuitar    URL del DELETE (solo si $puedeQuitar)
--}}
@php
    $firmado     = $orden->tieneFirma($tipo);
    $ccDefecto   = $ccPorDefecto ?? '';
    $puedeQuitar = $puedeQuitar ?? false;
@endphp

<div class="firma-block h-100">
    <div class="firma-block-head">
        <i class="bi bi-vector-pen"></i> {{ $titulo }}
        @if($firmado)
            <span class="badge bg-success ms-1"><i class="bi bi-check2"></i> Firmado</span>
        @endif
    </div>

    @if($firmado)
        <div class="firma-guardada">
            <img src="{{ $orden->firmaUrl($tipo) }}" alt="Firma de {{ $orden->firmanteNombre($tipo) }}">
            <div class="firma-datos">
                <div class="fw-semibold">{{ $orden->firmanteNombre($tipo) }}</div>
                @if($orden->{"firma_{$tipo}_cc"})
                    <div class="text-muted small">C.C. {{ $orden->{"firma_{$tipo}_cc"} }}</div>
                @endif
                <div class="text-muted small">{{ $rol }}</div>
                <div class="text-muted small">
                    <i class="bi bi-clock-history"></i>
                    Firmado el {{ $orden->{"firma_{$tipo}_at"}?->format('d/m/Y H:i') }}
                </div>
            </div>
            @if($puedeQuitar)
                <form method="POST" action="{{ $urlQuitar }}" onsubmit="return confirm('¿Eliminar esta firma?')" class="mt-2">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Eliminar firma</button>
                </form>
            @endif
        </div>
    @else
        <form method="POST" action="{{ $action }}" data-firma-form>
            @csrf
            <div class="firma-pad" data-firma-pad>
                <canvas></canvas>
                <span class="firma-pad-hint">Firme aquí</span>
                <button type="button" class="btn btn-link btn-sm firma-pad-clear" data-firma-clear>
                    <i class="bi bi-eraser"></i> Limpiar
                </button>
            </div>
            <input type="hidden" name="firma" data-firma-input>

            <div class="row g-2 mt-2">
                <div class="col-md-7">
                    <input type="text" name="nombre" class="form-control form-control-sm"
                           placeholder="Nombre de quien firma" required
                           value="{{ old('nombre', $nombrePorDefecto) }}">
                </div>
                <div class="col-md-5">
                    <input type="text" name="cc" class="form-control form-control-sm"
                           placeholder="C.C. / NIT" value="{{ old('cc', $ccDefecto) }}">
                </div>
            </div>
            <div class="d-grid mt-2">
                <button class="btn btn-primary btn-sm"><i class="bi bi-check2-circle me-1"></i>Guardar firma</button>
            </div>
            <small class="text-muted d-block mt-1">{{ $rol }}</small>
        </form>
    @endif
</div>
