<x-app-layout>
  <x-slot name="header">
    Sedes de {{ $cliente->nombre_empresa ?: $cliente->nombre_contacto }}
  </x-slot>

  <div class="container py-4">

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
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

    <div class="card shadow mb-4">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div>
            <h5 class="mb-1">Sedes y proyectos</h5>
            <p class="text-muted small mb-0">
              Cada sede es una ciudad o proyecto donde se atiende a este cliente.
              Sirve para saber a dónde fue cada servicio y, más adelante, qué equipos se recibieron en cada una.
            </p>
          </div>
          <a href="{{ route('clientes') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Clientes
          </a>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>Sede</th>
                <th>Ciudad</th>
                <th>Dirección</th>
                <th>Contacto</th>
                <th>Estado</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              @forelse($sucursales as $s)
                <tr>
                  <td>{{ $s->nombre }}</td>
                  <td>{{ $s->ciudad ?: '—' }}</td>
                  <td>{{ $s->direccion ?: '—' }}</td>
                  <td>
                    {{ $s->contacto ?: '—' }}
                    @if($s->telefono)<br><small class="text-muted">{{ $s->telefono }}</small>@endif
                  </td>
                  <td>
                    @if($s->activo)
                      <span class="badge bg-success">Activa</span>
                    @else
                      <span class="badge bg-secondary">Inactiva</span>
                    @endif
                  </td>
                  <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                      <button type="button" class="btn btn-outline-info btn-sm btn-editar-sede"
                              title="Editar"
                              data-id="{{ $s->id }}"
                              data-nombre="{{ $s->nombre }}"
                              data-ciudad="{{ $s->ciudad }}"
                              data-direccion="{{ $s->direccion }}"
                              data-contacto="{{ $s->contacto }}"
                              data-telefono="{{ $s->telefono }}">
                        <i class="bi bi-pencil"></i>
                      </button>

                      <form method="POST" action="{{ route('clientes.sucursales.toggle', [$cliente->id, $s->id]) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm {{ $s->activo ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                title="{{ $s->activo ? 'Desactivar' : 'Activar' }}">
                          <i class="bi {{ $s->activo ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                        </button>
                      </form>

                      <form method="POST" action="{{ route('clientes.sucursales.eliminar', [$cliente->id, $s->id]) }}"
                            onsubmit="return confirm('¿Eliminar esta sede?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">
                    Este cliente todavía no tiene sedes registradas.
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
        <h6 id="tituloFormSede" class="mb-3">Agregar sede</h6>

        <form method="POST" action="{{ route('clientes.sucursales.guardar', $cliente->id) }}">
          @csrf
          <input type="hidden" name="id" id="sedeId" value="">

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Nombre de la sede <span class="text-danger">*</span></label>
              <input name="nombre" id="sedeNombre" type="text" class="form-control" maxlength="255"
                     placeholder="Sede Norte, Proyecto Popayán…" value="{{ old('nombre') }}">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Ciudad</label>
              <input name="ciudad" id="sedeCiudad" type="text" class="form-control" maxlength="255"
                     value="{{ old('ciudad') }}">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Dirección</label>
              <input name="direccion" id="sedeDireccion" type="text" class="form-control" maxlength="255"
                     value="{{ old('direccion') }}">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Contacto en la sede</label>
              <input name="contacto" id="sedeContacto" type="text" class="form-control" maxlength="255"
                     value="{{ old('contacto') }}">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Teléfono</label>
              <input name="telefono" id="sedeTelefono" type="text" class="form-control" maxlength="100"
                     value="{{ old('telefono') }}">
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Guardar sede</button>
            <button type="button" class="btn btn-outline-secondary d-none" id="btnCancelarEdicion">Cancelar edición</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  @push('scripts')
  <script>
    // Editar reutiliza el mismo formulario de abajo: se rellena con los datos
    // de la fila y se manda el id, para no duplicar una pantalla entera.
    document.querySelectorAll('.btn-editar-sede').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.getElementById('sedeId').value = btn.dataset.id;
        document.getElementById('sedeNombre').value = btn.dataset.nombre || '';
        document.getElementById('sedeCiudad').value = btn.dataset.ciudad || '';
        document.getElementById('sedeDireccion').value = btn.dataset.direccion || '';
        document.getElementById('sedeContacto').value = btn.dataset.contacto || '';
        document.getElementById('sedeTelefono').value = btn.dataset.telefono || '';

        document.getElementById('tituloFormSede').textContent = 'Editar sede';
        document.getElementById('btnCancelarEdicion').classList.remove('d-none');
        document.getElementById('tituloFormSede').scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
    });

    document.getElementById('btnCancelarEdicion').addEventListener('click', function () {
      ['sedeId', 'sedeNombre', 'sedeCiudad', 'sedeDireccion', 'sedeContacto', 'sedeTelefono']
        .forEach(function (id) { document.getElementById(id).value = ''; });

      document.getElementById('tituloFormSede').textContent = 'Agregar sede';
      this.classList.add('d-none');
    });
  </script>
  @endpush
</x-app-layout>
