<x-app-layout>
  <x-slot name="header">Importar productos y precios</x-slot>

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

      <div class="card shadow mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h5 class="mb-0"><i class="bi bi-upload"></i> Importar / Actualizar productos y precios desde Excel</h5>
          <div class="d-flex gap-2">
            <a href="{{ route('productos.plantilla') }}" class="btn btn-outline-success btn-sm">
              <i class="bi bi-file-earmark-excel"></i> Descargar plantilla
            </a>
            <a href="{{ route('productos') }}" class="btn btn-outline-secondary btn-sm">
              <i class="bi bi-arrow-left"></i> Volver a productos
            </a>
          </div>
        </div>

        <div class="card-body">
          <div class="alert alert-info d-flex align-items-start gap-2 mb-3">
            <i class="bi bi-info-circle-fill mt-1"></i>
            <div>
              <strong>Esta plantilla cumple dos funciones:</strong> crear productos nuevos y actualizar precios de productos existentes.
              <br>
              <small>
                Si la referencia ya existe, se actualizan solo los campos que vengan llenos.
                Si no existe, se crea el producto (descripción → nombre, categoría default "Sin categoría").
                Si una misma referencia se repite con distinto <code>color_o_motivo</code>, esas filas se interpretan como variantes del mismo producto.
              </small>
            </div>
          </div>

          <form action="{{ route('productos.importar-excel') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
              <label for="archivo-import" class="form-label">Archivo Excel / CSV</label>
              <input id="archivo-import" type="file" name="archivo" class="form-control" accept=".xlsx,.xls,.csv" required>
              <small class="text-muted">Descarga primero la plantilla, llénala y súbela aquí.</small>
            </div>
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-upload"></i> Subir e importar
            </button>
          </form>

          @if(session('errores_import'))
            <div class="alert alert-warning mt-3 mb-0">
              <strong>Errores en el archivo:</strong>
              <ul class="mb-0 small">
                @foreach(session('errores_import') as $err)
                  <li>
                    Fila {{ $err['fila'] ?? '?' }}
                    @if(!empty($err['referencia'])) — <code>{{ $err['referencia'] }}</code> @endif
                    : {{ $err['mensaje'] ?? '' }}
                  </li>
                @endforeach
              </ul>
            </div>
          @endif

          <hr>

          <h6 class="mt-3">Formato esperado</h6>
          <p class="mb-2 text-muted small">
            Encabezados en la fila 1.
            La columna marcada en <span class="text-danger fw-bold">rojo</span> es la única obligatoria.
            Las columnas de precios usan el código de cada lista (no su nombre) y son dinámicas:
            si mañana agregas o quitas una lista de precios, la plantilla la refleja en la próxima descarga.
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
                  <td>Código único del producto.</td>
                </tr>
                <tr>
                  <td><code>nombre</code></td>
                  <td><span class="badge bg-secondary">No</span></td>
                  <td>Nombre corto del producto. Si la referencia es nueva y esto viene vacío, se intenta con descripción y, en último caso, la referencia.</td>
                </tr>
                <tr>
                  <td><code>descripcion</code></td>
                  <td><span class="badge bg-secondary">No</span></td>
                  <td>Descripción larga del producto.</td>
                </tr>
                <tr>
                  <td><code>unidad_venta</code></td>
                  <td><span class="badge bg-secondary">No</span></td>
                  <td>UND, CAJA, etc. Default: UND.</td>
                </tr>
                <tr>
                  <td><code>unidad_empaque</code></td>
                  <td><span class="badge bg-secondary">No</span></td>
                  <td>Default: UND.</td>
                </tr>
                <tr>
                  <td><code>peso_paca</code></td>
                  <td><span class="badge bg-secondary">No</span></td>
                  <td>Peso por paca en kilogramos (kg). Acepta decimales (ej. 12.5). Se usa para el cálculo de peso en la cotización.</td>
                </tr>
                <tr>
                  <td><code>cubicaje_paca</code></td>
                  <td><span class="badge bg-secondary">No</span></td>
                  <td>Cubicaje (volumen) por paca en metros cúbicos (m³). Acepta decimales (ej. 0.045). Se usa para el cálculo de volumen en la cotización.</td>
                </tr>
                <tr>
                  <td><code>unidades_por_paca</code></td>
                  <td><span class="badge bg-secondary">No</span></td>
                  <td>Cuántas unidades de venta trae una paca (ej. 12). Necesario para calcular el peso/volumen total según la cantidad pedida.</td>
                </tr>
                <tr>
                  <td><code>codigo_barras</code></td>
                  <td><span class="badge bg-secondary">No</span></td>
                  <td>Código de barras del producto (útil para exportaciones/aduanas).</td>
                </tr>
                <tr>
                  <td><code>color_o_motivo</code></td>
                  <td><span class="badge bg-secondary">No</span></td>
                  <td>Si trae valor, esa fila es una variante. Repite la misma referencia con distintos colores para crear varias variantes.</td>
                </tr>
                @foreach($listas as $lista)
                  <tr>
                    <td><code>{{ $lista->codigo }}</code></td>
                    <td><span class="badge bg-secondary">No</span></td>
                    <td>Precio para la lista <strong>{{ $lista->nombre }}</strong>. Vacío = no se actualiza.</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>

      @if($ultimas->count())
        <div class="card shadow">
          <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-clock-history"></i> Últimas importaciones</h6>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Fecha</th>
                    <th>Archivo</th>
                    <th>Usuario</th>
                    <th class="text-end">Filas</th>
                    <th class="text-end">Exitosas</th>
                    <th class="text-end">Con errores</th>
                    <th>Estado</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($ultimas as $u)
                    <tr>
                      <td>{{ $u->created_at->format('d/m/Y H:i') }}</td>
                      <td><small>{{ $u->nombre_archivo }}</small></td>
                      <td>{{ optional($u->usuario)->name ?? '—' }}</td>
                      <td class="text-end">{{ $u->total_filas }}</td>
                      <td class="text-end text-success">{{ $u->actualizaciones_exitosas }}</td>
                      <td class="text-end text-danger">{{ $u->actualizaciones_fallidas }}</td>
                      <td>
                        @if($u->estado === 'completado')
                          <span class="badge bg-success">Completado</span>
                        @elseif($u->estado === 'procesando')
                          <span class="badge bg-warning text-dark">Procesando</span>
                        @else
                          <span class="badge bg-secondary">{{ $u->estado }}</span>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      @endif

    </div>
  </div>
</x-app-layout>
