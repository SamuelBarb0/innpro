<x-app-layout>
    <x-slot name="header">{{ $orden->exists ? 'Editar Orden '.$orden->numero : 'Nueva Orden de Servicio' }}</x-slot>

    <div class="container py-4" style="max-width: 900px;">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h4 class="mb-4">{{ $orden->exists ? 'Editar Orden '.$orden->numero : 'Nueva Orden de Servicio' }}</h4>

          <form method="POST" action="{{ route('servicio.guardar') }}">
            @csrf
            <input type="hidden" name="id" value="{{ old('id', $orden->id) }}">

            <div class="row">
              <div class="col-md-8 mb-3">
                <label class="form-label">Título del trabajo <span class="text-danger">*</span></label>
                <input name="titulo" type="text" class="form-control"
                       value="{{ old('titulo', $orden->titulo) }}"
                       placeholder="Ej. Instalación CCTV — Sede norte">
                @error('titulo') <small class="text-danger">{{ $message }}</small> @enderror
              </div>

              <div class="col-md-4 mb-3">
                <label class="form-label">Prioridad <span class="text-danger">*</span></label>
                <select name="prioridad" class="form-select">
                  @foreach(\App\Models\OrdenServicio::PRIORIDADES as $val => $p)
                    <option value="{{ $val }}" {{ old('prioridad', $orden->prioridad ?: 'media') == $val ? 'selected' : '' }}>{{ $p['label'] }}</option>
                  @endforeach
                </select>
              </div>

              <div class="col-md-6 mb-3">
                <div class="d-flex justify-content-between align-items-center">
                  <label class="form-label mb-0">Cliente <span class="text-danger">*</span></label>
                  <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none"
                          data-bs-toggle="modal" data-bs-target="#modalProspectoOrden">
                    <i class="bi bi-person-plus"></i> Nuevo prospecto
                  </button>
                </div>
                <select name="cliente_id" id="selectClienteOrden" class="form-select mt-1">
                  <option value="">-- Seleccionar --</option>
                  @foreach($clientes as $c)
                    <option value="{{ $c->id }}" {{ old('cliente_id', $orden->cliente_id) == $c->id ? 'selected' : '' }}>
                      {{ $c->nombre_empresa ? $c->nombre_empresa.' — ' : '' }}{{ $c->nombre_contacto }}@if($c->es_temporal) · Prospecto @endif
                    </option>
                  @endforeach
                </select>
                @error('cliente_id') <small class="text-danger">{{ $message }}</small> @enderror
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label">Técnico asignado</label>
                <select name="tecnico_id" class="form-select">
                  <option value="">-- Sin asignar --</option>
                  @foreach($tecnicos as $id => $name)
                    <option value="{{ $id }}" {{ old('tecnico_id', $orden->tecnico_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                  @endforeach
                </select>
                @error('tecnico_id') <small class="text-danger">{{ $message }}</small> @enderror
              </div>

              <div class="col-12 mb-3">
                <label class="form-label">Descripción del problema / solicitud</label>
                <textarea name="descripcion_problema" class="form-control" rows="3"
                          placeholder="Describe el requerimiento del cliente...">{{ old('descripcion_problema', $orden->descripcion_problema) }}</textarea>
              </div>

              <div class="col-md-4 mb-3">
                <label class="form-label">Fecha de ingreso <span class="text-danger">*</span></label>
                <input name="fecha_ingreso" type="date" class="form-control"
                       value="{{ old('fecha_ingreso', optional($orden->fecha_ingreso)->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
                @error('fecha_ingreso') <small class="text-danger">{{ $message }}</small> @enderror
              </div>

              <div class="col-md-4 mb-3">
                <label class="form-label">Fecha estimada de entrega</label>
                <input name="fecha_estimada" type="date" class="form-control"
                       value="{{ old('fecha_estimada', optional($orden->fecha_estimada)->format('Y-m-d')) }}">
              </div>

              @if($verCostos)
              {{-- El costo lo define facturación: no se le muestra al técnico. --}}
              <div class="col-md-4 mb-3">
                <label class="form-label">Costo mano de obra</label>
                <div class="input-group">
                  <span class="input-group-text">$</span>
                  <input name="costo_mano_obra" type="number" step="0.01" min="0" class="form-control"
                         value="{{ old('costo_mano_obra', $orden->costo_mano_obra ?: '') }}" placeholder="0">
                </div>
              </div>
              @endif
            </div>

            <div class="d-flex justify-content-between mt-3">
              <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Guardar orden</button>
              <a href="{{ route('servicio.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
          </form>
        </div>
      </div>
    </div>

    {{-- Alta rápida de prospecto sin salir de la orden (pedido 8 de la reunión) --}}
    <div class="modal fade" id="modalProspectoOrden" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-person-plus"></i> Nuevo prospecto</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted small">
              Para abrirle una orden a alguien que todavía no es cliente. Queda marcado como
              prospecto; cuando se concrete, se completan sus datos desde Clientes.
            </p>

            <div id="prospectoOrdenError" class="alert alert-danger d-none"></div>

            <div class="mb-3">
              <label class="form-label">Nombre del contacto <span class="text-danger">*</span></label>
              <input type="text" id="prospNombre" class="form-control" maxlength="255">
            </div>
            <div class="mb-3">
              <label class="form-label">Empresa</label>
              <input type="text" id="prospEmpresa" class="form-control" maxlength="255">
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Teléfono</label>
                <input type="text" id="prospTelefono" class="form-control" maxlength="100">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Ciudad</label>
                <input type="text" id="prospCiudad" class="form-control" maxlength="255">
              </div>
            </div>
            <div class="mb-1">
              <label class="form-label">Correo</label>
              <input type="email" id="prospEmail" class="form-control" maxlength="255">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-primary" id="btnGuardarProspecto">Crear y seleccionar</button>
          </div>
        </div>
      </div>
    </div>

    @push('scripts')
    <script>
      document.getElementById('btnGuardarProspecto').addEventListener('click', function () {
        const boton = this;
        const error = document.getElementById('prospectoOrdenError');
        error.classList.add('d-none');

        const nombre = document.getElementById('prospNombre').value.trim();
        if (!nombre) {
          error.textContent = 'El nombre del prospecto es obligatorio.';
          error.classList.remove('d-none');
          return;
        }

        boton.disabled = true;

        fetch('{{ route('servicio.cliente.temporal') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
          },
          body: JSON.stringify({
            nombre_contacto: nombre,
            nombre_empresa: document.getElementById('prospEmpresa').value.trim(),
            telefono: document.getElementById('prospTelefono').value.trim(),
            ciudad: document.getElementById('prospCiudad').value.trim(),
            email: document.getElementById('prospEmail').value.trim(),
          }),
        })
          .then(async (r) => {
            const data = await r.json().catch(() => ({}));
            if (!r.ok) {
              // 422 de Laravel: {errors:{campo:[msg]}} o {message:"..."}
              const msg = data.errors
                ? Object.values(data.errors).flat().join(' ')
                : (data.message || 'No se pudo crear el prospecto.');
              throw new Error(msg);
            }
            return data;
          })
          .then((data) => {
            // Se agrega al select y queda elegido, sin recargar: la orden
            // conserva lo que ya se habia escrito.
            const select = document.getElementById('selectClienteOrden');
            const opcion = new Option(data.etiqueta, data.id, true, true);
            select.add(opcion);
            select.value = data.id;

            bootstrap.Modal.getInstance(document.getElementById('modalProspectoOrden')).hide();
            ['prospNombre', 'prospEmpresa', 'prospTelefono', 'prospCiudad', 'prospEmail']
              .forEach((id) => { document.getElementById(id).value = ''; });
          })
          .catch((e) => {
            error.textContent = e.message;
            error.classList.remove('d-none');
          })
          .finally(() => { boton.disabled = false; });
      });
    </script>
    @endpush
</x-app-layout>
