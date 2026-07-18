<x-app-layout>
    <x-slot name="header">Servicio Técnico</x-slot>

    <div class="container-fluid py-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
              <h4 class="mb-0">Órdenes de Servicio</h4>
              <small class="text-muted">Gestión de trabajos técnicos, bitácora y equipos</small>
            </div>
          </div>

          @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
          @endif

          <table id="ordenes-table" class="table-responsive w-full text-sm text-left">
            <thead class="text-xs uppercase bg-gray-100">
              <tr>
                <th>Acciones</th>
                <th># Orden</th>
                <th>Título</th>
                <th>Cliente</th>
                <th>Técnico</th>
                <th>Estado</th>
                <th>Prioridad</th>
                <th>Ingreso</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
      const table = $('#ordenes-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        scrollX: true,
        order: [[1, 'desc']],
        ajax: "{{ route('servicio.index') }}",
        columns: [
          { data:'action',        orderable:false, searchable:false },
          { data:'numero',        name:'numero' },
          { data:'titulo',        name:'titulo' },
          { data:'cliente',       orderable:false, searchable:false },
          { data:'tecnico',       orderable:false, searchable:false },
          { data:'estado',        name:'estado' },
          { data:'prioridad',     name:'prioridad' },
          { data:'fecha_ingreso', name:'fecha_ingreso' },
          { data:'total',         orderable:false, searchable:false },
        ],
        dom: "<'flex justify-between mb-4'<'relative'B>f>t<'flex justify-between items-center px-2 my-2'i<'pagination-wrapper'p>>",
        buttons: [
          { extend:'pageLength', className:'btn btn-outline-dark', text:'Filas ' },
          { extend:'excelHtml5', className:'btn btn-outline-success', text:'Excel' },
          {
            text:'Nueva orden', className:'btn btn-outline-primary',
            action: () => window.location.href = "{{ route('servicio.form') }}"
          }
        ],
        language: { url: '{{ asset("js/datatables/es-ES.json") }}' },
        lengthMenu: [[10,25,50,-1],[10,25,50,'Todos']]
      });
    });
    </script>
    @endpush
</x-app-layout>
