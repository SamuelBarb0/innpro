<x-app-layout>
  <x-slot name="header">Cotizador - Seleccionar Cliente</x-slot>

  <div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
          {{ session('error') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      @endif

      <div class="bg-white shadow-sm rounded-lg overflow-hidden">
        <div class="p-6">
          <h4 class="text-2xl font-semibold mb-4">Seleccionar Cliente para Cotizar</h4>
          
          <p class="text-muted mb-4">
            Seleccione el cliente para el cual desea generar una cotización.
            Se mostrarán los precios correspondientes a la lista de precios asignada al cliente.
          </p>

          @if($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          {{-- Prospectos: cotizar sin dar de alta un cliente completo --}}
          <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalProspecto">
              <i class="bi bi-person-plus"></i> Cotizar a un prospecto
            </button>
          </div>

          {{-- Filtros de búsqueda --}}
          <div class="card mb-4">
            <div class="card-body">
              <div class="row">
                <div class="col-md-4 mb-3">
                  <label class="form-label">Buscar por nombre</label>
                  <input type="text" class="form-control" id="buscarNombre" 
                         placeholder="Nombre del cliente...">
                </div>
                <div class="col-md-3 mb-3">
                  <label class="form-label">Ciudad</label>
                  <select class="form-select" id="filtroCiudad">
                    <option value="">Todas las ciudades</option>
                    @foreach($clientes->pluck('ciudad')->filter()->unique()->sort() as $ciudad)
                      <option value="{{ strtolower($ciudad) }}">{{ $ciudad }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-3 mb-3">
                  <label class="form-label">Lista de Precios</label>
                  <select class="form-select" id="filtroLista">
                    <option value="">Todas las listas</option>
                    @foreach($clientes->pluck('listaPrecio')->unique()->filter() as $lista)
                      <option value="{{ $lista->id }}">{{ $lista->nombre }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-2 mb-3">
                  <label class="form-label">&nbsp;</label>
                  <button class="btn btn-secondary w-100" id="btnLimpiarFiltros">
                    <i class="bi bi-x-circle"></i> Limpiar
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="row" id="clientesContainer">
            @forelse($clientes as $cliente)
              <div class="col-md-4 mb-4 cliente-card"
                   data-nombre="{{ strtolower($cliente->nombre_contacto) }}"
                   data-ciudad="{{ strtolower($cliente->ciudad ?? '') }}"
                   data-lista="{{ $cliente->lista_precio_id }}">
                <div class="card h-100">
                  <div class="card-body">
                    <h5 class="card-title">
                      {{ $cliente->nombre_contacto }}
                      @if($cliente->es_temporal)
                        <span class="badge bg-warning text-dark align-middle">Prospecto</span>
                      @endif
                    </h5>
                    <p class="card-text">
                      <small class="text-muted">
                        <i class="bi bi-geo-alt"></i> {{ $cliente->ciudad ?: 'Sin ciudad' }}<br>
                        <i class="bi bi-telephone"></i> {{ $cliente->telefono }}<br>
                        <i class="bi bi-envelope"></i> {{ $cliente->email }}<br>
                        <i class="bi bi-tag"></i> Lista: {{ $cliente->listaPrecio?->nombre ?? 'Sin lista' }}
                        @if(auth()->user()->hasRole('admin'))
                          <br><i class="bi bi-person"></i> Vendedor: {{ $cliente->vendedor?->name ?? 'Sin vendedor' }}
                        @endif
                      </small>
                    </p>
                    <form action="{{ route('catalogo.cliente') }}" method="POST">
                      @csrf
                      <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">
                      <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-cart"></i> Seleccionar
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            @empty
              <div class="col-12">
                <div class="alert alert-info">
                  <i class="bi bi-info-circle"></i> No tiene clientes asignados activos.
                </div>
              </div>
            @endforelse
          </div>

          {{-- Mensaje cuando no hay resultados --}}
          <div class="col-12" id="noResultados" style="display:none;">
            <div class="alert alert-warning">
              <i class="bi bi-search"></i> No se encontraron clientes con los filtros aplicados.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Alta rápida de prospecto: solo el nombre es obligatorio --}}
  <div class="modal fade" id="modalProspecto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form action="{{ route('catalogo.cliente.temporal') }}" method="POST" class="modal-content">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-person-plus"></i> Cotizar a un prospecto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small">
            Para cotizarle a alguien que todavía no es cliente. Se crea marcado como
            <strong>prospecto</strong>; cuando se concrete, se completan sus datos desde Clientes.
          </p>

          <div class="mb-3">
            <label class="form-label">Nombre del contacto <span class="text-danger">*</span></label>
            <input type="text" name="nombre_contacto" class="form-control" required maxlength="255"
                   value="{{ old('nombre_contacto') }}" autofocus>
          </div>

          <div class="mb-3">
            <label class="form-label">Lista de precios</label>
            <select name="lista_precio_id" class="form-select">
              @foreach($listas as $lista)
                <option value="{{ $lista->id }}"
                  @selected(old('lista_precio_id', $listaProspectos) == $lista->id)>
                  {{ $lista->nombre }}@if($lista->id == $listaProspectos) (estándar){{-- --}}@endif
                </option>
              @endforeach
            </select>
            <div class="form-text">
              Viene con la lista estándar de prospectos. Cámbiala solo si a este le cotizas con otra tarifa.
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Empresa</label>
            <input type="text" name="nombre_empresa" class="form-control" maxlength="255"
                   value="{{ old('nombre_empresa') }}">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Teléfono</label>
              <input type="text" name="telefono" class="form-control" maxlength="100"
                     value="{{ old('telefono') }}">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Ciudad</label>
              <input type="text" name="ciudad" class="form-control" maxlength="255"
                     value="{{ old('ciudad') }}">
            </div>
          </div>
          <div class="mb-1">
            <label class="form-label">Correo</label>
            <input type="email" name="email" class="form-control" maxlength="255"
                   value="{{ old('email') }}">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-cart"></i> Crear y cotizar
          </button>
        </div>
      </form>
    </div>
  </div>

  @push('scripts')
  <script>
    $(document).ready(function() {
      function filtrarClientes() {
        const busqueda = $('#buscarNombre').val().toLowerCase();
        const ciudadId = $('#filtroCiudad').val();
        const listaId = $('#filtroLista').val();
        let visibles = 0;

        $('.cliente-card').each(function() {
          const $card = $(this);
          const nombre = $card.data('nombre');
          const ciudad = $card.data('ciudad');
          const lista = $card.data('lista');

          let mostrar = true;

          // Filtro por nombre
          if (busqueda && !nombre.includes(busqueda)) {
            mostrar = false;
          }

          // Filtro por ciudad
          if (ciudadId && ciudad != ciudadId) {
            mostrar = false;
          }

          // Filtro por lista
          if (listaId && lista != listaId) {
            mostrar = false;
          }

          if (mostrar) {
            $card.show();
            visibles++;
          } else {
            $card.hide();
          }
        });

        // Mostrar mensaje si no hay resultados
        if (visibles === 0) {
          $('#noResultados').show();
        } else {
          $('#noResultados').hide();
        }
      }

      // Eventos de filtros
      $('#buscarNombre').on('keyup', function() {
        filtrarClientes();
      });

      $('#filtroCiudad, #filtroLista').on('change', function() {
        filtrarClientes();
      });

      $('#btnLimpiarFiltros').click(function() {
        $('#buscarNombre').val('');
        $('#filtroCiudad').val('');
        $('#filtroLista').val('');
        filtrarClientes();
      });
    });
  </script>
  @endpush
</x-app-layout>