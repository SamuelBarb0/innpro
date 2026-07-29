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
                <label class="form-label">Cliente <span class="text-danger">*</span></label>
                <select name="cliente_id" class="form-select">
                  <option value="">-- Seleccionar --</option>
                  @foreach($clientes as $c)
                    <option value="{{ $c->id }}" {{ old('cliente_id', $orden->cliente_id) == $c->id ? 'selected' : '' }}>
                      {{ $c->nombre_empresa ? $c->nombre_empresa.' — ' : '' }}{{ $c->nombre_contacto }}
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
</x-app-layout>
