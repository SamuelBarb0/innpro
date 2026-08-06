<x-app-layout>
  <x-slot name="header">
    Equipos en {{ $sucursal->etiqueta }}
  </x-slot>

  <div class="container py-4">

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    {{-- Los problemas de una importación se cuentan fila por fila, así que no
         pueden salir por el mismo canal que un guardado correcto. --}}
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
      </div>
    @endif

    <div class="d-flex justify-content-between align-items-start mb-3">
      <div>
        <h5 class="mb-1">{{ $cliente->nombre_empresa ?: $cliente->nombre_contacto }}</h5>
        <p class="text-muted small mb-0">
          Equipos recibidos para el proyecto de esta sede. Es independiente de la existencia
          general de bodega.
        </p>
      </div>
      <a href="{{ route('clientes.sucursales', $cliente->id) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Sedes
      </a>
    </div>

    {{-- Cargar la remisión de golpe. Registrar equipo por equipo sirve para un
         ajuste suelto, pero una remisión trae veinte líneas y nadie las teclea. --}}
    <div class="card shadow mb-4">
      <div class="card-body">
        <h6 class="mb-1">Cargar una remisión desde Excel</h6>
        <p class="text-muted small mb-3">
          Descarga la plantilla, escribe la <strong>referencia</strong> y la <strong>cantidad</strong> de cada equipo,
          y súbela. <strong>No hace falta llenar las columnas de cliente y sucursal</strong>: todo lo que subas aquí
          entra en <strong>{{ $sucursal->nombre }}</strong>.
        </p>

        <form method="POST" action="{{ route('clientes.sucursales.stock.importar', [$cliente->id, $sucursal->id]) }}"
              enctype="multipart/form-data" class="row g-2 align-items-center">
          @csrf
          <div class="col-md-4">
            <a class="btn btn-outline-success w-100" href="{{ route('clientes.sucursales.stock.plantilla', [$cliente->id, $sucursal->id]) }}">
              <i class="bi bi-file-earmark-excel"></i> Descargar plantilla
            </a>
          </div>
          <div class="col-md-6">
            <input type="file" name="archivo" class="form-control" accept=".xlsx,.xls,.csv" required>
          </div>
          <div class="col-md-2">
            <button class="btn btn-primary w-100"><i class="bi bi-upload"></i> Cargar</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card shadow mb-4">
      <div class="card-body">
        <h6 class="mb-3">Registrar recepción o salida</h6>

        <form method="POST" action="{{ route('clientes.sucursales.stock.guardar', [$cliente->id, $sucursal->id]) }}">
          @csrf
          <div class="row align-items-end">
            <div class="col-md-5 mb-3">
              <label class="form-label">Equipo <span class="text-danger">*</span></label>
              <select name="producto_id" class="form-select">
                <option value="">-- Seleccionar --</option>
                @foreach($productos as $p)
                  <option value="{{ $p->id }}" {{ old('producto_id') == $p->id ? 'selected' : '' }}>
                    {{ $p->nombre }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2 mb-3">
              <label class="form-label">Cantidad <span class="text-danger">*</span></label>
              <input type="number" name="cantidad" min="1" step="1" class="form-control"
                     value="{{ old('cantidad') }}">
            </div>
            <div class="col-md-2 mb-3">
              <label class="form-label">Movimiento</label>
              <select name="modo" class="form-select">
                <option value="sumar" {{ old('modo') === 'sumar' ? 'selected' : '' }}>Recibir (+)</option>
                <option value="restar" {{ old('modo') === 'restar' ? 'selected' : '' }}>Retirar (−)</option>
                <option value="set" {{ old('modo') === 'set' ? 'selected' : '' }}>Dejar en</option>
              </select>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Motivo</label>
              <input type="text" name="motivo" class="form-control" maxlength="500"
                     placeholder="Remisión 1234…" value="{{ old('motivo') }}">
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Registrar</button>
        </form>
      </div>
    </div>

    <div class="card shadow mb-4">
      <div class="card-body">
        <h6 class="mb-3">Existencias en esta sede</h6>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Equipo</th>
                <th class="text-end">Cantidad</th>
              </tr>
            </thead>
            <tbody>
              @forelse($existencias as $e)
                <tr>
                  <td>{{ $e->producto?->nombre ?? '(producto eliminado)' }}</td>
                  <td class="text-end"><strong>{{ number_format($e->cantidad_disponible, 0) }}</strong></td>
                </tr>
              @empty
                <tr>
                  <td colspan="2" class="text-center text-muted py-4">
                    Todavía no se ha registrado ningún equipo en esta sede.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card shadow">
      <div class="card-body">
        <h6 class="mb-3">Últimos movimientos</h6>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Equipo</th>
                <th>Movimiento</th>
                <th class="text-end">Cantidad</th>
                <th>Motivo</th>
                <th>Usuario</th>
              </tr>
            </thead>
            <tbody>
              @forelse($movimientos as $m)
                <tr>
                  <td class="text-muted small">{{ $m->created_at?->format('d/m/Y H:i') }}</td>
                  <td>{{ $m->producto?->nombre ?? '—' }}</td>
                  <td>
                    <span class="badge bg-{{ $m->tipo_movimiento === 'entrada' ? 'success' : 'warning' }}">
                      {{ ucfirst($m->tipo_movimiento) }}
                    </span>
                  </td>
                  <td class="text-end">{{ $m->cantidad }}</td>
                  <td class="small">{{ $m->motivo }}</td>
                  <td class="small">{{ $m->usuario?->name ?? '—' }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">Sin movimientos todavía.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
