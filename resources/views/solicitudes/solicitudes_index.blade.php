<x-app-layout>
  <x-slot name="header">Gestión de Solicitudes de Cotización</x-slot>

  <div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      {{-- Mensajes de éxito/error --}}
      @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
          {{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      @endif
      
      @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
          {{ session('error') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      @endif

      <div class="bg-white shadow-sm rounded-lg overflow-hidden">
        <div class="p-6">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <h4 class="text-2xl font-semibold mb-0">Listado de Solicitudes</h4>
            <div class="d-flex align-items-center gap-2">
              <label for="filtroTipo" class="form-label mb-0 small text-muted">Tipo de cotización</label>
              <select id="filtroTipo" class="form-select form-select-sm" style="width:auto;">
                <option value="">Todos</option>
                @foreach($tipos as $clave => $etiqueta)
                  <option value="{{ $clave }}">{{ $etiqueta }}</option>
                @endforeach
                <option value="sin_tipo">Sin tipo</option>
              </select>
            </div>
          </div>

          <table id="solicitudes-table" class="table-responsive w-full text-sm text-left">
            <thead class="text-xs uppercase bg-gray-100">
              <tr>
                <th>Acciones</th>
                <th>Nº Solicitud</th>
                <th>Cliente</th>
                <th>Vendedor</th>
                <th>Fecha</th>
                <th>Items</th>
                <th>Monto</th>
                <th>Tipo</th>
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
  <script>
  document.addEventListener('DOMContentLoaded', () => {
    const table = $('#solicitudes-table').DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      scrollX: true,
      ajax: {
        url: "{{ route('solicitudes') }}",
        data: d => { d.tipo_cotizacion = $('#filtroTipo').val(); }
      },
      columns: [
        { data:'action', orderable:false, searchable:false },
        { data:'numero_solicitud', name:'numero_solicitud' },
        { data:'cliente_nombre', name:'cliente_nombre' },
        { data:'vendedor', name:'vendedor' },
        { data:'fecha', name:'created_at' },
        { data:'total_items', name:'total_items', searchable:false },
        { data:'monto_formateado', name:'monto_total' },
        { data:'tipo', name:'tipo_cotizacion' },
        { data:'estado_badge', name:'estado' }
      ],
      dom: "<'flex justify-between mb-4'<'relative'B>f>t<'flex justify-between items-center px-2 my-2'i<'pagination-wrapper'p>>",
      buttons: [
        { extend:'pageLength', className:'btn btn-outline-dark', text:'Filas ' },
        { extend:'colvis', className:'btn btn-outline-dark', text:'Columnas', columns:':not(.noVis)' },
        { extend:'excelHtml5', className:'btn btn-outline-success', text:'Excel' },
        {
          text:'<i class="bi bi-funnel"></i> Pendientes',
          className:'btn btn-outline-warning',
          action: function(e, dt, node, config) {
            if ($(node).hasClass('active')) {
              $(node).removeClass('active');
              dt.column(7).search('').draw();
            } else {
              $(node).addClass('active');
              dt.column(7).search('Pendiente').draw();
            }
          }
        },
        {
          text:'<i class="bi bi-funnel"></i> Aplicadas',
          className:'btn btn-outline-success',
          action: function(e, dt, node, config) {
            if ($(node).hasClass('active')) {
              $(node).removeClass('active');
              dt.column(7).search('').draw();
            } else {
              $(node).addClass('active');
              dt.column(7).search('Aplicada').draw();
            }
          }
        },
        {
          text:'<i class="bi bi-file-earmark-excel"></i> Exportar Todo',
          className:'btn btn-outline-info',
          action: function() {
            $('#modalExportarExcel').modal('show');
          }
        }
      ],
      language: { url: '{{ asset("js/datatables/es-ES.json") }}' },
      lengthMenu: [[10,25,50,-1],[10,25,50,'Todos']],
      order: [[4, 'desc']] // Ordenar por fecha descendente
    });

    // El filtro de tipo va en la peticion (ver `ajax.data`), asi que basta con
    // recargar para que el servidor aplique el where.
    $('#filtroTipo').on('change', () => table.ajax.reload());

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

  // Funciones para los modales
  function verDetalle(solicitudId) {
    $('#modalDetalleContent').html('<div class="text-center"><div class="spinner-border" role="status"></div></div>');
    $('#modalDetalle').modal('show');
    
    $.get(`/solicitudes/${solicitudId}/detalle`, function(data) {
      $('#modalDetalleContent').html(data);
    }).fail(function(xhr) {
      $('#modalDetalleContent').html(
        '<div class="alert alert-danger">Error al cargar el detalle: ' + 
        (xhr.responseJSON?.error || 'Error desconocido') + '</div>'
      );
    });
  }

  function marcarAplicada(solicitudId) {
    // Cargar el detalle en el modal para poder agregar observaciones
    verDetalle(solicitudId);
  }

  function confirmarAplicar(solicitudId) {
    const observaciones = $('#observacionesAdmin').val();
    
    // Mostrar loading
    $('#modalDetalleContent').append(
      '<div class="loading-overlay" style="position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,0.8);display:flex;align-items:center;justify-content:center;z-index:1000;">' +
      '<div class="spinner-border" role="status"></div></div>'
    );
    
    $.post(`/solicitudes/${solicitudId}/aplicar`, {
      _token: '{{ csrf_token() }}',
      observaciones: observaciones
    }, function(response) {
      if (response.success) {
        $('#modalDetalle').modal('hide');
        
        // Mostrar mensaje de éxito
        const alert = `
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            ${response.mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        `;
        $('.max-w-7xl').prepend(alert);
        
        // Recargar tabla
        $('#solicitudes-table').DataTable().ajax.reload();
      }
    }).fail(function(xhr) {
      $('.loading-overlay').remove();
      alert('Error: ' + (xhr.responseJSON?.mensaje || 'Error al aplicar la solicitud'));
    });
  }
  
  // Función para exportar Excel con filtros
  function exportarExcel() {
    const form = $('#formExportarExcel');
    form.submit();
    $('#modalExportarExcel').modal('hide');
  }
  </script>
  @endpush

  <!-- Modal para ver detalle -->
  <div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Detalle de Solicitud de Cotización</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="modalDetalleContent">
          <div class="text-center">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Modal para Exportar Excel -->
  <div class="modal fade" id="modalExportarExcel" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Exportar Solicitudes a Excel</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form id="formExportarExcel" action="{{ route('solicitudes.exportar-excel') }}" method="GET">
          <div class="modal-body">
            <p class="text-muted mb-4">
              Este reporte incluirá tres hojas:
              <ul class="small text-muted">
                <li><strong>Resumen:</strong> Información general de cada solicitud</li>
                <li><strong>Detalle:</strong> Todos los items de todas las solicitudes</li>
                <li><strong>Productos:</strong> Resumen de productos más solicitados</li>
              </ul>
            </p>
            
            <div class="mb-3">
              <label class="form-label">Estado</label>
              <select name="estado" class="form-select">
                <option value="">Todos los estados</option>
                <option value="pendiente">Solo Pendientes</option>
                <option value="aplicada">Solo Aplicadas</option>
              </select>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Fecha Desde</label>
              <input type="date" name="fecha_desde" class="form-control">
            </div>
            
            <div class="mb-3">
              <label class="form-label">Fecha Hasta</label>
              <input type="date" name="fecha_hasta" class="form-control">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-success">
              <i class="bi bi-download"></i> Descargar Excel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</x-app-layout>