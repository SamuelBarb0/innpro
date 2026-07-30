<x-app-layout>
    <x-slot name="header">Orden {{ $orden->numero }}</x-slot>

    <div class="container-fluid py-4">

      @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif
      @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
      @endif

      {{-- ===== Encabezado ===== --}}
      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
              <div class="d-flex align-items-center gap-2 mb-1">
                <h4 class="mb-0">{{ $orden->numero }}</h4>
                <span class="badge bg-{{ $orden->estadoColor() }}">{{ $orden->estadoLabel() }}</span>
                <span class="badge bg-{{ $orden->prioridadColor() }}">{{ $orden->prioridadLabel() }}</span>
              </div>
              <div class="fw-semibold">{{ $orden->titulo }}</div>
              <small class="text-muted">
                <i class="bi bi-person-badge"></i> {{ $orden->cliente?->nombre_empresa ?: $orden->cliente?->nombre_contacto }}@if($orden->sucursal)
                  <span class="ms-2"><i class="bi bi-geo-alt"></i> {{ $orden->sucursal->etiqueta }}</span>
                @endif
                &nbsp;·&nbsp; <i class="bi bi-tools"></i> {{ $orden->tecnico?->name ?? 'Sin técnico' }}
                &nbsp;·&nbsp; <i class="bi bi-calendar-event"></i> Ingreso {{ optional($orden->fecha_ingreso)->format('d/m/Y') }}
                @if($orden->fecha_estimada) &nbsp;·&nbsp; <i class="bi bi-flag"></i> Estimada {{ $orden->fecha_estimada->format('d/m/Y') }} @endif
              </small>
            </div>
            <div class="d-flex gap-2">
              <a href="{{ route('servicio.pdf', $orden->id) }}" class="btn btn-primary btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
              <a href="{{ route('servicio.form', $orden->id) }}" class="btn btn-outline-info btn-sm"><i class="bi bi-pencil me-1"></i>Editar datos</a>
              <a href="{{ route('servicio.index') }}" class="btn btn-outline-secondary btn-sm">Volver</a>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-4">
        {{-- ============ Columna izquierda ============ --}}
        <div class="col-12 col-xl-7">

          {{-- Estado / diagnóstico --}}
          <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-clipboard-pulse"></i> Estado y diagnóstico</h6></div>
            <div class="card-body">
              <form method="POST" action="{{ route('servicio.actualizar', $orden->id) }}">
                @csrf
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                      @foreach($estados as $val => $e)
                        <option value="{{ $val }}" {{ $orden->estado == $val ? 'selected' : '' }}>{{ $e['label'] }}</option>
                      @endforeach
                    </select>
                  </div>
                  @if($verCostos)
                  {{-- El costo lo determina facturación: el técnico no lo ve ni lo diligencia. --}}
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Costo mano de obra</label>
                    <div class="input-group">
                      <span class="input-group-text">$</span>
                      <input name="costo_mano_obra" type="number" step="0.01" min="0" class="form-control" value="{{ $orden->costo_mano_obra }}">
                    </div>
                  </div>
                  @endif
                  <div class="col-12 mb-3">
                    <label class="form-label">Diagnóstico técnico</label>
                    <textarea name="diagnostico" class="form-control" rows="3">{{ $orden->diagnostico }}</textarea>
                  </div>
                  <div class="col-12 mb-3">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2">{{ $orden->observaciones }}</textarea>
                  </div>
                </div>
                <button class="btn btn-primary btn-sm"><i class="bi bi-check2 me-1"></i>Guardar cambios</button>
              </form>
            </div>
          </div>

          {{-- B5: Equipos --}}
          @include('servicio.partials.items', [
            'titulo' => 'Equipos de seguridad', 'icono' => 'bi-camera-video', 'tipo' => 'equipo',
            'coleccion' => $orden->equipos, 'orden' => $orden, 'productos' => $productos,
          ])

          {{-- B5/B3: Repuestos --}}
          @include('servicio.partials.items', [
            'titulo' => 'Repuestos utilizados', 'icono' => 'bi-wrench-adjustable', 'tipo' => 'repuesto',
            'coleccion' => $orden->repuestos, 'orden' => $orden, 'productos' => $productos,
          ])

          {{-- B3: Evidencia --}}
          <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-images"></i> Evidencia fotográfica</h6></div>
            <div class="card-body">
              <form method="POST" action="{{ route('servicio.imagenes.subir', $orden->id) }}" enctype="multipart/form-data" class="row g-2 align-items-end mb-3">
                @csrf
                <div class="col-md-5"><input type="file" name="imagen" accept="image/*" class="form-control form-control-sm" required></div>
                <div class="col-md-5"><input type="text" name="descripcion" class="form-control form-control-sm" placeholder="Descripción (opcional)"></div>
                <div class="col-md-2 d-grid"><button class="btn btn-outline-primary btn-sm">Subir</button></div>
              </form>
              @if($orden->imagenes->isEmpty())
                <p class="text-muted small mb-0">Sin imágenes cargadas.</p>
              @else
                <div class="row g-2">
                  @foreach($orden->imagenes as $img)
                    <div class="col-6 col-md-4">
                      <div class="position-relative">
                        <a href="{{ $img->url }}" target="_blank"><img src="{{ $img->url }}" class="img-fluid rounded border" style="aspect-ratio:4/3;object-fit:cover;width:100%;"></a>
                        <form method="POST" action="{{ route('servicio.imagenes.eliminar', [$orden->id, $img->id]) }}" class="position-absolute top-0 end-0 m-1" onsubmit="return confirm('¿Eliminar imagen?')">
                          @csrf @method('DELETE')
                          <button class="btn btn-danger btn-sm py-0 px-1"><i class="bi bi-x"></i></button>
                        </form>
                        @if($img->descripcion)<small class="d-block text-muted text-truncate">{{ $img->descripcion }}</small>@endif
                      </div>
                    </div>
                  @endforeach
                </div>
              @endif
            </div>
          </div>

          {{-- Colector de firmas del formato técnico --}}
          <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-vector-pen"></i> Firmas del formato técnico</h6></div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-12 col-lg-6">
                  @include('servicio.partials.firma', [
                    'orden'            => $orden,
                    'tipo'             => 'tecnico',
                    'titulo'           => 'Técnico responsable',
                    'rol'              => 'Técnico responsable',
                    'action'           => route('servicio.firmar', [$orden->id, 'tecnico']),
                    'nombrePorDefecto' => $orden->tecnico?->name,
                    'ccPorDefecto'     => null,
                    'puedeQuitar'      => $esAdmin,
                    'urlQuitar'        => route('servicio.firmar.quitar', [$orden->id, 'tecnico']),
                  ])
                </div>
                <div class="col-12 col-lg-6">
                  @include('servicio.partials.firma', [
                    'orden'            => $orden,
                    'tipo'             => 'cliente',
                    'titulo'           => 'Cliente — recibido a conformidad',
                    'rol'              => 'Cliente — recibido a conformidad',
                    'action'           => route('servicio.firmar', [$orden->id, 'cliente']),
                    'nombrePorDefecto' => $orden->cliente?->nombre_contacto,
                    'ccPorDefecto'     => $orden->cliente?->numero_identificacion,
                    'puedeQuitar'      => $esAdmin,
                    'urlQuitar'        => route('servicio.firmar.quitar', [$orden->id, 'cliente']),
                  ])
                </div>
              </div>
              <small class="text-muted d-block mt-2">
                <i class="bi bi-info-circle"></i>
                Las firmas capturadas aquí se imprimen en el PDF del formato técnico.
                El cliente también puede firmar desde el enlace de seguimiento cuando la orden está finalizada.
              </small>
            </div>
          </div>
        </div>

        {{-- ============ Columna derecha ============ --}}
        <div class="col-12 col-xl-5">

          {{-- Resumen de costos (oculto para el técnico: lo determina facturación) --}}
          @if($verCostos)
          <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-cash-stack"></i> Resumen del servicio</h6></div>
            <div class="card-body">
              <div class="d-flex justify-content-between py-1"><span class="text-muted">Mano de obra</span><span>$ {{ number_format($orden->costo_mano_obra, 0) }}</span></div>
              <div class="d-flex justify-content-between py-1"><span class="text-muted">Equipos + repuestos</span><span>$ {{ number_format($orden->total_items, 0) }}</span></div>
              <hr class="my-2">
              <div class="d-flex justify-content-between py-1 fw-bold" style="font-size:1.15rem;color:var(--in-indigo,#241D5E)"><span>Total</span><span>$ {{ number_format($orden->total, 0) }}</span></div>
              <div class="d-flex justify-content-between py-1 small text-muted"><span>Horas registradas</span><span>{{ number_format($orden->horas_totales, 1) }} h</span></div>
            </div>
          </div>
          @else
          <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-clock-history"></i> Resumen del trabajo</h6></div>
            <div class="card-body">
              <div class="d-flex justify-content-between py-1"><span class="text-muted">Equipos y repuestos</span><span>{{ $orden->items->count() }}</span></div>
              <div class="d-flex justify-content-between py-1"><span class="text-muted">Horas registradas</span><span>{{ number_format($orden->horas_totales, 1) }} h</span></div>
            </div>
          </div>
          @endif

          {{-- B4: Bitácora --}}
          <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h6 class="mb-0"><i class="bi bi-journal-text"></i> Bitácora del ticket</h6>
              @if($esAdmin)
                <form method="POST" action="{{ route('servicio.bitacora.visible', $orden->id) }}">
                  @csrf
                  <button class="btn btn-sm {{ $orden->bitacora_visible_cliente ? 'btn-success' : 'btn-outline-secondary' }}" title="Visibilidad para el cliente">
                    <i class="bi {{ $orden->bitacora_visible_cliente ? 'bi-eye' : 'bi-eye-slash' }} me-1"></i>
                    {{ $orden->bitacora_visible_cliente ? 'Visible al cliente' : 'Privada' }}
                  </button>
                </form>
              @endif
            </div>
            <div class="card-body">

              {{-- Enlace público de seguimiento para el cliente --}}
              @if($esAdmin)
                <div class="mb-3">
                  <label class="form-label small mb-1"><i class="bi bi-link-45deg"></i> Enlace de seguimiento para el cliente</label>
                  <div class="input-group input-group-sm">
                    <input type="text" id="segLink" class="form-control" readonly value="{{ $orden->urlSeguimiento() }}">
                    <button class="btn btn-outline-primary" type="button" onclick="copiarSeg()"><i class="bi bi-clipboard"></i></button>
                    <a class="btn btn-outline-secondary" href="{{ $orden->urlSeguimiento() }}" target="_blank"><i class="bi bi-box-arrow-up-right"></i></a>
                  </div>
                  <small class="text-muted">
                    {{ $orden->bitacora_visible_cliente
                        ? 'El cliente puede ver el estado y la bitácora (solo lectura).'
                        : 'El cliente ve el estado, pero la bitácora está oculta. Habilítala con el botón de arriba.' }}
                  </small>
                </div>
                <hr class="my-3">
              @endif

              @if($puedeBitacora)
                <form method="POST" action="{{ route('servicio.bitacora.agregar', $orden->id) }}" enctype="multipart/form-data" class="mb-4">
                  @csrf
                  <div class="mb-2">
                    <textarea name="descripcion" class="form-control" rows="2" placeholder="Describe el avance del trabajo..." required></textarea>
                  </div>
                  <div class="row g-2 mb-2">
                    <div class="col-6"><input type="number" step="0.5" min="0" name="horas_trabajadas" class="form-control form-control-sm" placeholder="Horas"></div>
                    <div class="col-6"><input type="file" name="fotos[]" accept="image/*" multiple class="form-control form-control-sm"></div>
                  </div>
                  <input type="text" name="observaciones" class="form-control form-control-sm mb-2" placeholder="Observaciones (opcional)">
                  <button class="btn btn-primary btn-sm w-100"><i class="bi bi-plus-lg me-1"></i>Registrar entrada</button>
                </form>
              @else
                <p class="text-muted small">La bitácora es de solo lectura para tu rol.</p>
              @endif

              @if($orden->bitacora->isEmpty())
                <p class="text-muted small mb-0">Aún no hay entradas en la bitácora.</p>
              @else
                <ul class="timeline list-unstyled mb-0">
                  @foreach($orden->bitacora as $b)
                    <li class="timeline-item">
                      <div class="timeline-dot"></div>
                      <div class="timeline-content">
                        <div class="d-flex justify-content-between align-items-start">
                          <div>
                            <span class="fw-semibold">{{ $b->tecnico?->name ?? 'Técnico' }}</span>
                            <small class="text-muted d-block">{{ $b->created_at->format('d/m/Y H:i') }}
                              @if($b->horas_trabajadas) · {{ number_format($b->horas_trabajadas,1) }} h @endif
                            </small>
                          </div>
                          @if($puedeBitacora)
                            <form method="POST" action="{{ route('servicio.bitacora.eliminar', [$orden->id, $b->id]) }}" onsubmit="return confirm('¿Eliminar entrada?')">
                              @csrf @method('DELETE')
                              <button class="btn btn-link btn-sm text-danger p-0"><i class="bi bi-trash"></i></button>
                            </form>
                          @endif
                        </div>
                        <div class="mt-1">{{ $b->descripcion }}</div>
                        @if($b->observaciones)<small class="text-muted d-block mt-1"><i class="bi bi-info-circle"></i> {{ $b->observaciones }}</small>@endif
                        @if($b->fotos->isNotEmpty())
                          <div class="d-flex flex-wrap gap-1 mt-2">
                            @foreach($b->fotos as $f)
                              <a href="{{ $f->url }}" target="_blank"><img src="{{ $f->url }}" class="rounded border" style="width:56px;height:56px;object-fit:cover;"></a>
                            @endforeach
                          </div>
                        @endif
                      </div>
                    </li>
                  @endforeach
                </ul>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>

    @include('servicio.partials.firma-assets')

    @push('scripts')
    <script>
      function copiarSeg() {
        const inp = document.getElementById('segLink');
        if (!inp) return;
        inp.select(); inp.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(inp.value).then(() => {
          if (window.Swal) { Swal.fire({icon:'success',title:'Enlace copiado',timer:1200,showConfirmButton:false}); }
        });
      }
    </script>
    @endpush

    @push('styles')
    <style>
      .timeline { position: relative; padding-left: 1.4rem; }
      .timeline::before { content:""; position:absolute; left:6px; top:4px; bottom:4px; width:2px; background:rgba(36,29,94,.12); }
      .timeline-item { position: relative; padding-bottom: 1.1rem; }
      .timeline-dot { position:absolute; left:-1.4rem; top:4px; width:14px; height:14px; border-radius:50%; background:#5DCEBA; box-shadow:0 0 0 3px rgba(93,206,186,.25); }
      .timeline-content { background:#f7f9fd; border:1px solid rgba(36,29,94,.07); border-radius:12px; padding:.7rem .85rem; }
    </style>
    @endpush
</x-app-layout>
