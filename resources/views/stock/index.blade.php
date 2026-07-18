<x-app-layout>
  <x-slot name="header">Gestión de Stock</x-slot>

  <div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

      @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4">
          {{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      @endif
      @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show mb-4">
          {{ session('warning') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      @endif
      @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4">
          {{ session('error') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      @endif

      {{-- Tarjetas resumen --}}
      <div class="row mb-4">
        <div class="col-md-4">
          <div class="card border-warning h-100">
            <div class="card-body">
              <h6 class="card-title text-warning mb-1">
                <i class="bi bi-exclamation-triangle"></i> Productos con stock bajo
              </h6>
              <p class="card-text display-6 mb-0">{{ $productosConStockBajo }}</p>
              <small class="text-muted">
                @if($productosConStockBajo === 0)
                  Ningún producto está por debajo de su mínimo.
                @else
                  Por debajo del stock mínimo configurado.
                @endif
              </small>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card border-danger h-100">
            <div class="card-body">
              <h6 class="card-title text-danger mb-1">
                <i class="bi bi-x-circle"></i> Productos sin stock
              </h6>
              <p class="card-text display-6 mb-0">{{ $productosSinStock }}</p>
              <small class="text-muted">
                @if($productosSinStock === 0)
                  Todos los productos tienen unidades disponibles.
                @else
                  Con 0 unidades disponibles.
                @endif
              </small>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card border-info h-100">
            <div class="card-body">
              <h6 class="card-title text-info mb-2">
                <i class="bi bi-clock-history"></i> Historial
              </h6>
              <a href="{{ route('stock.historial') }}" class="btn btn-outline-info">
                Ver movimientos
              </a>
              <small class="d-block text-muted mt-2">Entradas, salidas y ajustes.</small>
            </div>
          </div>
        </div>
      </div>

      {{-- Importar Excel --}}
      <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h5 class="mb-0"><i class="bi bi-upload"></i> Importar / Actualizar inventario desde Excel</h5>
          <a href="{{ route('stock.plantilla-importacion') }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-excel"></i> Descargar plantilla Excel
          </a>
        </div>
        <div class="card-body">
          <div class="alert alert-info d-flex align-items-start gap-2 mb-3">
            <i class="bi bi-info-circle-fill mt-1"></i>
            <div>
              <strong>Esta importación solo actualiza el stock de productos y variantes que ya existen.</strong><br>
              <small>
                No crea productos nuevos. Si una referencia o SKU del Excel no existe en el sistema, esa fila se reportará como error.
                Para dar de alta productos o variantes, usa primero
                <a href="{{ route('productos.form') }}" class="alert-link">Productos → Nuevo</a>.
              </small>
            </div>
          </div>

          <form action="{{ route('stock.importar-excel') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
              <label class="form-label">Archivo Excel / CSV</label>
              <input type="file" name="archivo" class="form-control" accept=".xlsx,.xls,.csv" required>
              <small class="text-muted">Descarga primero la plantilla, llénala y súbela aquí.</small>
            </div>
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-upload"></i> Subir e importar
            </button>
          </form>

          <hr>

          <h6 class="mt-3">Formato esperado</h6>
          <p class="mb-2 text-muted">
            Encabezados en la fila 1. La columna <code>referencia</code> puede ser el código del producto o el SKU de una variante.
            Las columnas marcadas en <span class="text-danger fw-bold">rojo</span> son obligatorias.
          </p>
          <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle">
              <thead class="table-light">
                <tr>
                  <th>Columna</th>
                  <th>Obligatorio</th>
                  <th>Descripción</th>
                </tr>
              </thead>
              <tbody>
                <tr class="table-danger">
                  <td><code class="text-danger fw-bold">referencia</code></td>
                  <td><span class="badge bg-danger">Sí</span></td>
                  <td>Referencia del producto o SKU de la variante.</td>
                </tr>
                <tr class="table-danger">
                  <td><code class="text-danger fw-bold">cantidad</code></td>
                  <td><span class="badge bg-danger">Sí</span></td>
                  <td>Cantidad (entero positivo). Se interpreta según la columna <code>modo</code>.</td>
                </tr>
                <tr>
                  <td><code>modo</code></td>
                  <td><span class="badge bg-secondary">No</span></td>
                  <td><code>set</code> (reemplaza, default), <code>sumar</code> o <code>restar</code>.</td>
                </tr>
                <tr>
                  <td><code>stock_minimo</code></td>
                  <td><span class="badge bg-secondary">No</span></td>
                  <td>Cantidad mínima para alerta de stock bajo.</td>
                </tr>
                <tr>
                  <td><code>stock_maximo</code></td>
                  <td><span class="badge bg-secondary">No</span></td>
                  <td>Cantidad máxima de referencia.</td>
                </tr>
                <tr>
                  <td><code>ubicacion</code></td>
                  <td><span class="badge bg-secondary">No</span></td>
                  <td>Ubicación física (bodega, estante, etc).</td>
                </tr>
              </tbody>
            </table>
          </div>

          @if(session('errores_stock'))
            <div class="alert alert-warning mt-3">
              <strong>Errores en el archivo:</strong>
              <ul class="mb-0">
                @foreach(session('errores_stock') as $err)
                  <li>Fila {{ $err['fila'] }} — <code>{{ $err['referencia'] }}</code>: {{ $err['mensaje'] }}</li>
                @endforeach
              </ul>
            </div>
          @endif
        </div>
      </div>

    </div>
  </div>
  {{-- padding-zstd: relleno para desplazar el límite de frame del zstd del CDN de Hostinger (evita corrupción que dejaba /stock en blanco). No eliminar. --}}
  <!-- {{ str_repeat('zstd-pad ', 700) }} -->
</x-app-layout>
