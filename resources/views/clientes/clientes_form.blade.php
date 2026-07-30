<x-app-layout>
    <x-slot name="header">
        {{ $cliente->exists ? 'Editar Cliente' : 'Nuevo Cliente' }}
    </x-slot>

    <div class="container py-4">
      <div class="card shadow">
        <div class="card-body">
          <form method="POST" action="{{ route('clientes.guardar') }}">
            @csrf
            <input type="hidden" name="id" value="{{ old('id', $cliente->id) }}">

            <div class="row">
              {{-- Identificación --}}
              <div class="col-md-6 mb-3">
                <label class="form-label">NIT/CC (Identificación) <span class="text-danger">*</span></label>
                <input name="numero_identificacion" type="text"
                       class="form-control"
                       value="{{ old('numero_identificacion', $cliente->numero_identificacion) }}">
                @error('numero_identificacion') <small class="text-danger">{{ $message }}</small> @enderror
              </div>

              {{-- Contacto --}}
              <div class="col-md-6 mb-3">
                <label class="form-label">Nombre de contacto <span class="text-danger">*</span></label>
                <input name="nombre_contacto" type="text"
                       class="form-control"
                       value="{{ old('nombre_contacto', $cliente->nombre_contacto) }}">
                @error('nombre_contacto') <small class="text-danger">{{ $message }}</small> @enderror
              </div>

              {{-- Nombre Empresa --}}
              <div class="col-md-6 mb-3">
                <label class="form-label">Nombre de empresa</label>
                <input name="nombre_empresa" type="text"
                       class="form-control"
                       value="{{ old('nombre_empresa', $cliente->nombre_empresa) }}">
                @error('nombre_empresa') <small class="text-danger">{{ $message }}</small> @enderror
              </div>

              {{-- Email --}}
              <div class="col-md-6 mb-3">
                <label class="form-label">Email <span class="text-danger">*</span></label>
                <input name="email" type="email"
                       class="form-control"
                       value="{{ old('email', $cliente->email) }}">
                @error('email') <small class="text-danger">{{ $message }}</small> @enderror
              </div>

              {{-- Teléfono --}}
              <div class="col-md-6 mb-3">
                <label class="form-label">Teléfono</label>
                <input name="telefono" type="text"
                       class="form-control"
                       value="{{ old('telefono', $cliente->telefono) }}">
                @error('telefono') <small class="text-danger">{{ $message }}</small> @enderror
              </div>

              {{-- País --}}
              <div class="col-md-6 mb-3">
                <label class="form-label">País <span class="text-danger">*</span></label>
                <input name="pais" type="text"
                       class="form-control"
                       value="{{ old('pais', $cliente->pais) }}"
                       placeholder="Ej. Colombia">
                @error('pais') <small class="text-danger">{{ $message }}</small> @enderror
              </div>

              {{-- Ciudad --}}
              <div class="col-md-6 mb-3">
                <label class="form-label">Ciudad <span class="text-danger">*</span></label>
                <input name="ciudad" type="text"
                       class="form-control"
                       value="{{ old('ciudad', $cliente->ciudad) }}"
                       placeholder="Ej. Bogotá">
                @error('ciudad') <small class="text-danger">{{ $message }}</small> @enderror
              </div>

              {{-- Vendedor --}}
              <div class="col-md-6 mb-3">
                <label class="form-label">Vendedor <span class="text-danger">*</span></label>
                <select name="vendedor_id" class="form-select">
                  <option value="">-- Seleccionar --</option>
                  @foreach($vendedores as $id => $name)
                    <option value="{{ $id }}"
                      {{ old('vendedor_id', $cliente->vendedor_id) == $id ? 'selected' : '' }}>
                      {{ $name }}
                    </option>
                  @endforeach
                </select>
                @error('vendedor_id') <small class="text-danger">{{ $message }}</small> @enderror
              </div>

              {{-- Lista de Precio --}}
              <div class="col-md-6 mb-3">
                <label class="form-label">Lista de Precio <span class="text-danger">*</span></label>
                <select name="lista_precio_id" class="form-select">
                  <option value="">-- Seleccionar --</option>
                  @foreach($listas as $id => $nombre)
                    <option value="{{ $id }}"
                      {{ old('lista_precio_id', $cliente->lista_precio_id) == $id ? 'selected' : '' }}>
                      {{ $nombre }}
                    </option>
                  @endforeach
                </select>
                @error('lista_precio_id') <small class="text-danger">{{ $message }}</small> @enderror
              </div>

              {{-- Activo (solo al editar) --}}
              @if($cliente->exists)
                <div class="col-md-6 mb-3 d-flex align-items-end">
                  <div class="form-check">
                    <input type="hidden" name="activo" value="0">
                    <input type="checkbox"
                           class="form-check-input"
                           id="activo"
                           name="activo"
                           value="1"
                           {{ old('activo', $cliente->activo) ? 'checked' : '' }}>
                    <label class="form-check-label" for="activo">
                      Cliente activo
                    </label>
                  </div>
                </div>
              @endif
            </div>

            <div class="d-flex justify-content-between mt-4">
              <button type="submit" class="btn btn-primary">Guardar</button>
              <div class="d-flex gap-2">
                @if($cliente->exists)
                  {{-- Las sedes viven en su propia pantalla para que agregar una
                       no obligue a guardar lo que se esté editando aquí. --}}
                  <a href="{{ route('clientes.sucursales', $cliente->id) }}" class="btn btn-outline-primary">
                    <i class="bi bi-geo-alt"></i> Sedes ({{ $cliente->sucursales()->count() }})
                  </a>
                @endif
                <a href="{{ route('clientes') }}" class="btn btn-outline-secondary">Cancelar</a>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
</x-app-layout>
