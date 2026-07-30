<x-app-layout>
  <x-slot name="header">Precios · {{ $lista->nombre }}</x-slot>

  <div class="container py-4">

    @foreach(['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $clave => $color)
      @if(session($clave))
        <div class="alert alert-{{ $color }} alert-dismissible fade show">
          {{ session($clave) }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      @endif
    @endforeach

    @if(session('erroresImportacion') && count(session('erroresImportacion')))
      <div class="alert alert-warning">
        <strong>Filas que no se pudieron cargar:</strong>
        <ul class="mb-0 mt-2 small">
          @foreach(session('erroresImportacion') as $e)
            <li>Fila {{ $e['fila'] }}: {{ $e['mensaje'] }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
      </div>
    @endif

    <div class="d-flex justify-content-between align-items-start mb-3">
      <div>
        <h5 class="mb-1">{{ $lista->nombre }} <code class="ms-1">{{ $lista->codigo }}</code></h5>
        <p class="text-muted small mb-0">
          {{ $precios->count() }} producto(s) con precio en esta lista.
          @if($sinPrecio > 0)
            <span class="text-warning">Faltan {{ $sinPrecio }} sin precio.</span>
          @endif
        </p>
      </div>
      <a href="{{ route('listas-precios') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Listas
      </a>
    </div>

    <div class="card shadow mb-4">
      <div class="card-body">
        <h6 class="mb-3">Cargar precios desde Excel</h6>
        <p class="text-muted small">
          Descarga la plantilla: viene con todo el catálogo y el precio que cada producto tiene
          hoy en esta lista. Cambia solo la columna <strong>precio</strong> y vuelve a subirla.
          Las filas que dejes vacías no se tocan.
        </p>

        <div class="row align-items-end">
          <div class="col-md-4 mb-3">
            <a href="{{ route('listas-precios.plantilla', $lista->id) }}" class="btn btn-outline-primary w-100">
              <i class="bi bi-download"></i> Descargar plantilla
            </a>
          </div>
          <div class="col-md-8 mb-3">
            <form method="POST" action="{{ route('listas-precios.importar', $lista->id) }}"
                  enctype="multipart/form-data" class="d-flex gap-2">
              @csrf
              <input type="file" name="archivo" class="form-control" accept=".xlsx,.xls,.csv" required>
              <button type="submit" class="btn btn-primary text-nowrap">
                <i class="bi bi-upload"></i> Cargar
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow">
      <div class="card-body">
        <h6 class="mb-3">Precios actuales</h6>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Producto</th>
                <th class="text-end">Precio</th>
              </tr>
            </thead>
            <tbody>
              @forelse($precios as $p)
                <tr>
                  <td>{{ $p->producto?->nombre ?? '(producto eliminado)' }}</td>
                  <td class="text-end">$ {{ number_format($p->precio, 0, ',', '.') }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="2" class="text-center text-muted py-4">
                    Esta lista todavía no tiene precios. Descarga la plantilla y cárgalos.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
