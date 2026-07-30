<x-app-layout>
  <x-slot name="header">Listas de precios</x-slot>

  <div class="container py-4">

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
      </div>
    @endif

    <div class="card shadow mb-4">
      <div class="card-body">
        <h5 class="mb-1">Listas disponibles</h5>
        <p class="text-muted small">
          Cada cliente cotiza con la lista que tenga asignada. Para darle precios propios a un
          cliente, crea una lista para él, cárgale los precios y asígnasela desde Clientes.
        </p>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Código</th>
                <th class="text-end">Productos con precio</th>
                <th class="text-end">Clientes</th>
                <th>Estado</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              @forelse($listas as $l)
                <tr>
                  <td>
                    {{ $l->nombre }}
                    @if($l->descripcion)<br><small class="text-muted">{{ $l->descripcion }}</small>@endif
                  </td>
                  <td><code>{{ $l->codigo }}</code></td>
                  <td class="text-end">{{ $l->precios_productos_count }}</td>
                  <td class="text-end">{{ $l->clientes_count }}</td>
                  <td>
                    <span class="badge bg-{{ $l->activo ? 'success' : 'secondary' }}">
                      {{ $l->activo ? 'Activa' : 'Inactiva' }}
                    </span>
                  </td>
                  <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                      <a href="{{ route('listas-precios.precios', $l->id) }}"
                         class="btn btn-outline-success btn-sm" title="Ver y cargar precios">
                        <i class="bi bi-currency-dollar"></i>
                      </a>
                      <button type="button" class="btn btn-outline-info btn-sm btn-editar-lista" title="Editar"
                              data-id="{{ $l->id }}" data-nombre="{{ $l->nombre }}"
                              data-codigo="{{ $l->codigo }}" data-descripcion="{{ $l->descripcion }}"
                              data-orden="{{ $l->orden }}">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <form method="POST" action="{{ route('listas-precios.toggle', $l->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm {{ $l->activo ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                title="{{ $l->activo ? 'Desactivar' : 'Activar' }}">
                          <i class="bi {{ $l->activo ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No hay listas de precios.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card shadow">
      <div class="card-body">
        <h6 id="tituloFormLista" class="mb-3">Nueva lista</h6>

        <form method="POST" action="{{ route('listas-precios.guardar') }}">
          @csrf
          <input type="hidden" name="id" id="listaId" value="">

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Nombre <span class="text-danger">*</span></label>
              <input name="nombre" id="listaNombre" type="text" class="form-control" maxlength="255"
                     placeholder="Precio general, Precio Comercial Andina…" value="{{ old('nombre') }}">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Código <span class="text-danger">*</span></label>
              <input name="codigo" id="listaCodigo" type="text" class="form-control" maxlength="50"
                     placeholder="general, andina…" value="{{ old('codigo') }}">
              <small class="text-muted">Letras, números, guiones. Sin espacios.</small>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Descripción</label>
              <input name="descripcion" id="listaDescripcion" type="text" class="form-control" maxlength="500"
                     value="{{ old('descripcion') }}">
            </div>
            <div class="col-md-2 mb-3">
              <label class="form-label">Orden</label>
              <input name="orden" id="listaOrden" type="number" min="0" class="form-control"
                     value="{{ old('orden', 0) }}">
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Guardar lista</button>
            <button type="button" class="btn btn-outline-secondary d-none" id="btnCancelarLista">Cancelar edición</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  @push('scripts')
  <script>
    document.querySelectorAll('.btn-editar-lista').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.getElementById('listaId').value = btn.dataset.id;
        document.getElementById('listaNombre').value = btn.dataset.nombre || '';
        document.getElementById('listaCodigo').value = btn.dataset.codigo || '';
        document.getElementById('listaDescripcion').value = btn.dataset.descripcion || '';
        document.getElementById('listaOrden').value = btn.dataset.orden || 0;

        document.getElementById('tituloFormLista').textContent = 'Editar lista';
        document.getElementById('btnCancelarLista').classList.remove('d-none');
        document.getElementById('tituloFormLista').scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
    });

    document.getElementById('btnCancelarLista').addEventListener('click', function () {
      ['listaId', 'listaNombre', 'listaCodigo', 'listaDescripcion'].forEach(function (id) {
        document.getElementById(id).value = '';
      });
      document.getElementById('listaOrden').value = 0;
      document.getElementById('tituloFormLista').textContent = 'Nueva lista';
      this.classList.add('d-none');
    });
  </script>
  @endpush
</x-app-layout>
