<div class="card shadow-sm mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0"><i class="bi {{ $icono }}"></i> {{ $titulo }}</h6>
    <small class="text-muted">{{ $coleccion->count() }} ítem(s)</small>
  </div>
  <div class="card-body">

    {{-- Formulario de alta --}}
    <form method="POST" action="{{ route('servicio.items.agregar', $orden->id) }}" class="row g-2 align-items-end mb-3">
      @csrf
      <input type="hidden" name="tipo" value="{{ $tipo }}">
      <div class="col-md-4">
        <label class="form-label small mb-1">Del inventario (opcional)</label>
        <select name="producto_id" class="form-select form-select-sm" onchange="if(this.value){const t=this.options[this.selectedIndex].dataset.nombre; this.closest('form').querySelector('[name=descripcion]').value=t;}">
          <option value="">— Manual —</option>
          @foreach($productos as $p)
            <option value="{{ $p->id }}" data-nombre="{{ $p->nombre }}">{{ $p->nombre }}{{ $p->referencia ? ' ('.$p->referencia.')' : '' }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label small mb-1">Descripción <span class="text-danger">*</span></label>
        <input type="text" name="descripcion" class="form-control form-control-sm" required>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Cant.</label>
        <input type="number" step="0.01" min="0.01" name="cantidad" class="form-control form-control-sm" value="1" required>
      </div>
      @if($verCostos)
      <div class="col-md-2">
        <label class="form-label small mb-1">Precio u.</label>
        <input type="number" step="0.01" min="0" name="precio_unitario" class="form-control form-control-sm" value="0">
      </div>
      @endif
      <div class="col-12">
        <button class="btn btn-outline-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Agregar {{ $tipo }}</button>
      </div>
    </form>

    {{-- Listado --}}
    @if($coleccion->isEmpty())
      <p class="text-muted small mb-0">Sin {{ $tipo == 'equipo' ? 'equipos' : 'repuestos' }} registrados.</p>
    @else
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr class="text-muted small">
            <th>Descripción</th><th class="text-end">Cant.</th>
            @if($verCostos)<th class="text-end">Precio u.</th><th class="text-end">Subtotal</th>@endif
            <th></th>
          </tr></thead>
          <tbody>
            @foreach($coleccion as $it)
              <tr>
                <td>
                  {{ $it->descripcion }}
                  @if($it->producto)<span class="badge bg-light text-muted border ms-1"><i class="bi bi-box-seam"></i> inventario</span>@endif
                  @if($it->notas)<small class="d-block text-muted">{{ $it->notas }}</small>@endif
                </td>
                <td class="text-end">{{ rtrim(rtrim(number_format($it->cantidad,2),'0'),'.') }}</td>
                @if($verCostos)
                  <td class="text-end">$ {{ number_format($it->precio_unitario,0) }}</td>
                  <td class="text-end fw-semibold">$ {{ number_format($it->subtotal,0) }}</td>
                @endif
                <td class="text-end">
                  <form method="POST" action="{{ route('servicio.items.eliminar', [$orden->id, $it->id]) }}" onsubmit="return confirm('¿Eliminar ítem?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-link btn-sm text-danger p-0"><i class="bi bi-trash"></i></button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>
