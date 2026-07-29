<x-app-layout>
  <x-slot name="header">Productos</x-slot>

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
          <h4 class="text-2xl font-semibold mb-4">Listado de Productos</h4>

          <table id="productos-table" class="table-responsive w-full text-sm text-left">
            <thead class="text-xs uppercase bg-gray-100">
              <tr>
                <th>Acciones</th>
                <th>Imagen</th>
                <th>Referencia</th>
                <th>Nombre</th>
                <th>Unidad Venta</th>
                <th>Unidad Empaque</th>
                <th>¿Maneja Extensión?</th>
                <th>Variantes</th>
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
    const table = $('#productos-table').DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      scrollX: true,
      ajax: "{{ route('productos') }}",
      columns: [
        { data:'action',       orderable:false, searchable:false },
        { data:'imagen',       orderable:false, searchable:false },
        { data:'referencia',   name:'referencia' },
        { data:'nombre',       name:'nombre' },
        { data:'unidad_venta', name:'unidad_venta' },
        { data:'unidad_empaque', name:'unidad_empaque' },
        { data:'tiene_extension', name:'tiene_extension' },
        { data:'variantes',    name:'tiene_variantes' },
        { data:'estado',       name:'activo' },
      ],
      dom: "<'flex justify-between mb-4'<'relative'B>f>t<'flex justify-between items-center px-2 my-2'i<'pagination-wrapper'p>>",
      buttons: [
        { extend:'pageLength', className:'btn btn-outline-dark', text:'Filas ' },
        { extend:'colvis',     className:'btn btn-outline-dark', text:'Columnas', columns:':not(.noVis)' },
        { extend:'excelHtml5', className:'btn btn-outline-success', text:'Excel' },
        {
          text:'Nuevo', className:'btn btn-outline-primary',
          action: () => window.location.href = "{{ route('productos.form') }}"
        },
        {
          text:'<i class="bi bi-currency-dollar"></i> Actualizar Precios',
          className:'btn btn-outline-warning',
          action: () => window.location.href = "{{ route('productos.historial-precios') }}"
        },
        {
          text:'<i class="bi bi-upload"></i> Importar',
          className:'btn btn-outline-success',
          titleAttr:'Importar productos y precios desde Excel',
          action: () => window.location.href = "{{ route('productos.importar') }}"
        }
      ],
      language: { url: '{{ asset("js/datatables/es-ES.json") }}' },
      lengthMenu: [[10,25,50,-1],[10,25,50,'Todos']]
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

  // Funciones para los modales
  function verVariantes(productoId) {
    $.get(`/productos/${productoId}/variantes-ajax`, function(data) {
      $('#modalVariantesContent').html(data);
      $('#modalVariantes').modal('show');
    });
  }

  function verImagenes(productoId) {
    $.get(`/productos/${productoId}/imagenes-ajax`, function(data) {
      $('#modalImagenesContent').html(data);
      $('#modalImagenes').modal('show');
    });
  }

  function verPrecios(productoId) {
    $.get(`/productos/${productoId}/precios-ajax`, function(data) {
      $('#modalPreciosContent').html(data);
      $('#modalPrecios').modal('show');
    });
  }
  </script>
{{-- Agregar esta función JavaScript junto con las otras funciones --}}
<script>
// Variable global para almacenar el producto actual
let productoActualId = null;

function verStock(productoId) {
  // Guardar el ID del producto actual
  productoActualId = productoId;
  
  // Actualizar el enlace del botón con el producto_id
  $('#btnIrGestionStock').attr('href', '{{ route("stock.index") }}?producto_id=' + productoId);
  
  // Cargar el contenido del modal
  $.get(`/productos/${productoId}/stock-ajax`, function(data) {
    $('#modalStockContent').html(data);
    $('#modalStock').modal('show');
  });
}
</script>
  @endpush

<!-- Modal para ver stock -->
<div class="modal fade" id="modalStock" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Stock del Producto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalStockContent">
        <div class="text-center">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Cargando...</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="#" id="btnIrGestionStock" class="btn btn-primary">
          <i class="bi bi-box-seam"></i> Ir a Gestión de Stock (Filtrado)
        </a>
        <a href="{{ route('stock.index') }}" class="btn btn-outline-secondary">
          <i class="bi bi-box-seam"></i> Ver Todo el Stock
        </a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
  <!-- Modal para actualizar precios desde Excel -->
  <div class="modal fade" id="modalActualizarPrecios" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form action="{{ route('productos.actualizar-precios-excel') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title">Actualizar Precios desde Excel</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Archivo Excel</label>
              <input type="file" name="archivo" class="form-control" accept=".xlsx,.xls" required>
              <small class="text-muted">Formato: Referencia | Export1 | Export2 | Local1 | Local2 | Local3 | Local4</small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Actualizar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal para ver variantes -->
  <div class="modal fade" id="modalVariantes" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Variantes del Producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="modalVariantesContent">
          <div class="text-center">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal para ver imágenes -->
  <div class="modal fade" id="modalImagenes" tabindex="-1">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Imágenes del Producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="modalImagenesContent">
          <div class="text-center">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal para ver precios -->
  <div class="modal fade" id="modalPrecios" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Precios del Producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="modalPreciosContent">
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