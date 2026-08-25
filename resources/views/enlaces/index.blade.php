<x-app-layout>
  <x-slot name="header">Enlaces de Acceso al Catálogo</x-slot>

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
          <h4 class="text-2xl font-semibold mb-4">Gestión de Enlaces de Acceso</h4>
          
          <p class="text-muted mb-4">
            Aquí puede crear y gestionar enlaces temporales para que sus clientes accedan al catálogo de productos 
            y generen solicitudes de cotización sin necesidad de autenticarse.
          </p>

          <table id="enlaces-table" class="table-responsive w-full text-sm text-left">
            <thead class="text-xs uppercase bg-gray-100">
              <tr>
                <th class="noVis"><input type="checkbox" class="lote-todas" title="Seleccionar los de esta página"></th>
                <th>Acciones</th>
                <th>Cliente</th>
                <th>Estado</th>
                <th>Creado por</th>
                <th>Fecha Creación</th>
                <th>Expira en</th>
                <th>Visitas</th>
                <th>Solicitudes</th>
                <th>Mostrar Precios</th>
                <th>Mostrar Stock</th>
                <th>Último Acceso</th>
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
    const table = $('#enlaces-table').DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      scrollX: true,
      ajax: "{{ route('enlaces') }}",
      columns: [
        { data:'seleccion', orderable:false, searchable:false, className:'noVis text-center' },
        { data:'action', orderable:false, searchable:false },
        { data:'cliente_nombre', name:'cliente_nombre' },
        { data:'estado', orderable:false, searchable:false },
        { data:'creado_por_nombre', orderable:false },
        { data:'fecha_creacion', name:'created_at' },
        { data:'fecha_expiracion', name:'expira_en' },
        { data:'visitas', name:'visitas' },
        { data:'solicitudes_count', orderable:false, searchable:false },
        { data:'mostrar_precios_badge', name:'mostrar_precios' },
        { data:'mostrar_stock_badge', name:'mostrar_stock' },
        { data:'ultimo_acceso_formateado', name:'ultimo_acceso' }
      ],
      order: [[4, 'desc']], // Ordenar por fecha de creación descendente
      dom: "<'flex justify-between mb-4'<'relative'B>f>t<'flex justify-between items-center px-2 my-2'i<'pagination-wrapper'p>>",
      buttons: [
        { extend:'pageLength', className:'btn btn-outline-dark', text:'Filas ' },
        { extend:'colvis', className:'btn btn-outline-dark', text:'Columnas', columns:':not(.noVis)' },
        { extend:'excelHtml5', className:'btn btn-outline-success', text:'Excel' },
        {
          text:'Nuevo Enlace', 
          className:'btn btn-outline-primary',
          action: () => window.location.href = "{{ route('enlaces.crear') }}"
        }
      ],
      language: { url: '{{ asset("js/datatables/es-ES.json") }}' },
      lengthMenu: [[10,25,50,-1],[10,25,50,'Todos']]
    });

    AccionesEnLote({
      tabla: table,
      selector: '#enlaces-table',
      url: "{{ route('enlaces.lote') }}",
      urlIds: "{{ route('enlaces') }}",
      etiqueta: { singular:'enlace', plural:'enlaces' },
      acciones: [
        { clave:'activar',    texto:'Activar',    clase:'btn-outline-success' },
        { clave:'desactivar', texto:'Desactivar', clase:'btn-outline-warning' },
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

  // Función para copiar enlace
  function copiarEnlace(url) {
    navigator.clipboard.writeText(url).then(() => {
      mostrarNotificacion('Enlace copiado al portapapeles', 'success');
    }).catch(() => {
      // Fallback para navegadores antiguos
      const input = document.createElement('input');
      input.value = url;
      document.body.appendChild(input);
      input.select();
      document.execCommand('copy');
      document.body.removeChild(input);
      mostrarNotificacion('Enlace copiado al portapapeles', 'success');
    });
  }

  // Función para copiar texto de un input
  function copiarTexto(inputId) {
    const input = document.getElementById(inputId);
    input.select();
    document.execCommand('copy');
    mostrarNotificacion('Copiado al portapapeles', 'success');
  }

  // Ver detalle del enlace
  function verDetalle(enlaceId) {
    $.get(`/enlaces/${enlaceId}/detalle`, function(data) {
      $('#modalDetalleContent').html(data);
      $('#modalDetalle').modal('show');
    }).fail(function(xhr) {
      mostrarNotificacion(xhr.responseJSON?.error || 'Error al cargar el detalle', 'danger');
    });
  }

// Cambiar estado
function cambiarEstado(enlaceId, activo) {
    const mensaje = activo ? '¿Desea activar este enlace?' : '¿Desea desactivar este enlace?';
    
    if (!confirm(mensaje)) return;
    
    $.post(`/enlaces/${enlaceId}/cambiar-estado`, {
        _token: '{{ csrf_token() }}',
        activo: activo ? 1 : 0  // Convertir a 1 o 0 antes de enviar
    }, function(response) {
        if (response.success) {
            $('#enlaces-table').DataTable().ajax.reload();
            mostrarNotificacion(response.mensaje, 'success');
        }
    }).fail(function(xhr) {
        mostrarNotificacion(xhr.responseJSON?.mensaje || 'Error al cambiar el estado', 'danger');
    });
}

  // Función para mostrar notificaciones
  function mostrarNotificacion(mensaje, tipo = 'info') {
    const alertHtml = `
      <div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    `;
    
    const $alert = $(alertHtml);
    $('.max-w-7xl').prepend($alert);
    
    // Auto cerrar después de 5 segundos
    setTimeout(() => {
      $alert.alert('close');
    }, 5000);
  }
  </script>
  @endpush

  <!-- Modal para ver detalle -->
  <div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Detalle del Enlace</h5>
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
</x-app-layout>