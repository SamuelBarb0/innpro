<x-app-layout>
    <x-slot name="header">Clientes</x-slot>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
          <div class="p-6">
            <h4 class="text-2xl font-semibold mb-4">Clientes</h4>

            @if (session('success'))
              <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
              <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <table id="clientes-table" class="table-responsive w-full text-sm text-left">
              <thead class="text-xs uppercase bg-gray-100">
                <tr>
                  <th class="noVis"><input type="checkbox" class="lote-todas" title="Seleccionar los de esta página"></th>
                  <th>Acciones</th>
                  <th>NIT/CC</th>
                  <th>Contacto</th>
                  <th>Empresa</th>
                  <th>Email</th>
                  <th>Teléfono</th>
                  <th>País</th>
                  <th>Ciudad</th>
                  <th>Vendedor</th>
                  <th>Lista Precio</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    @push('scripts')
  <script src="{{ asset('js/acciones-lote.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
      const table = $('#clientes-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        scrollX: true,
        ajax: "{{ route('clientes') }}",
        columns: [
          { data:'seleccion',             orderable:false, searchable:false, className:'noVis text-center' },
          { data:'action',                orderable:false, searchable:false },
          { data:'numero_identificacion', name:'numero_identificacion' },
          { data:'nombre_contacto',       name:'nombre_contacto' },
          { data:'nombre_empresa',        name:'nombre_empresa' },
          { data:'email',                 name:'email' },
          { data:'telefono',              name:'telefono' },
          { data:'pais',                  name:'pais' },
          { data:'ciudad',                name:'ciudad' },
          { data:'vendedor',              orderable:false, searchable:false },
          { data:'lista_precio',          orderable:false, searchable:false },
          { data:'estado',                name:'activo', orderable:true, searchable:false },
        ],
        dom: "<'flex justify-between mb-4'<'relative'B>f>t<'flex justify-between items-center px-2 my-2'i<'pagination-wrapper'p>>",
        buttons: [
          { extend:'pageLength', className:'btn btn-outline-dark', text:'Filas ' },
          { extend:'colvis',     className:'btn btn-outline-dark', text:'Columnas', columns:':not(.noVis)' },
          { extend:'excelHtml5', className:'btn btn-outline-success', text:'Excel' },
          {
            text:'Nuevo', className:'btn btn-outline-primary',
            action: () => window.location.href = "{{ route('clientes.form') }}"
          }
        ],
        language: { url: '{{ asset("js/datatables/es-ES.json") }}' },
        lengthMenu: [[10,25,50,-1],[10,25,50,'Todos']]
      });

      AccionesEnLote({
        tabla: table,
        selector: '#clientes-table',
        url: "{{ route('clientes.lote') }}",
        urlIds: "{{ route('clientes') }}",
        etiqueta: { singular:'cliente', plural:'clientes' },
        acciones: [
          { clave:'activar',    texto:'Activar',    clase:'btn-outline-success' },
          { clave:'desactivar', texto:'Desactivar', clase:'btn-outline-warning' },
          { clave:'eliminar',   texto:'Eliminar',   clase:'btn-outline-danger', destructiva:true },
        ],
      });

      table.on('buttons-action', () => {
        setTimeout(() => {
          $('.dt-button-collection')
            .addClass('bg-white border rounded shadow-md mt-2 p-2')
            .css({ position:'absolute','z-index':999,top:'calc(100% + .5rem)',left:0 });
          $('.dt-button-collection button')
            .removeClass()
            .addClass('block w-full text-left px-4 py-2 rounded hover:bg-gray-100');
        }, 50);
      });
    });

    </script>
    @endpush
</x-app-layout>
