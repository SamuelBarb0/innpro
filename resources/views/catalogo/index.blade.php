{{-- resources/views/catalogo/index.blade.php - Solo para flujo B (vendedores) --}}
<x-app-layout>
  <x-slot name="header">
    Cotizador
    @if($cliente)
      <span class="badge bg-info ms-2">Cotizando para: {{ $cliente->nombre_contacto }}</span>
    @endif
  </x-slot>

  @push('styles')
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .producto-card { transition: transform .2s; cursor: pointer; }
    .producto-card:hover { transform: translateY(-5px); }
    .cart-badge { position: absolute; top: -8px; right: -8px; }
#cartSidebar { 
  position: fixed; 
  top: 0; 
  right: -400px; 
  width: 400px; 
  height: 100vh;
  background: #fff; 
  box-shadow: -2px 0 5px rgba(0,0,0,.1);
  transition: right .3s; 
  z-index: 1050;
  display: flex;
  flex-direction: column;
}
#cartItems {
  overflow-y: auto;
  overflow-x: hidden;
  max-height: 100%;
}
@media (max-width: 768px) {
  #cartSidebar {
    width: 100%;
    right: -100%;
  }
}
    #cartSidebar.show { right:0; }
    .loading-overlay { position:fixed; top:0; left:0; width:100%; height:100%;
                       background:rgba(255,255,255,.9); z-index:9999;
                       display:flex; align-items:center; justify-content:center; }
    
    /* Contenedor de imagen con fondo para mejor visualización */
    .producto-imagen-container {
      height: 200px;
      background-color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      border-bottom: 1px solid #dee2e6;
      position: relative;
      overflow: hidden;
    }

    .producto-imagen {
      height: 100%;
      width: 100%;
      object-fit: contain;
      object-position: center;
      transition: transform .25s ease;
    }

    .producto-card:hover .producto-imagen {
      transform: scale(1.05);
    }

    .producto-imagen-overlay {
      position: absolute;
      inset: 0;
      background: rgba(0,0,0,.35);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      opacity: 0;
      transition: opacity .2s ease;
      pointer-events: none;
    }

    .producto-card:hover .producto-imagen-overlay {
      opacity: 1;
    }
    
    /* Estilos para la navegación del carrusel */
    .carousel-pagination {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 0;
      width: 100%;
    }
    
    .carousel-nav-btn {
      background: #87ceeb;
      border: none;
      color: #333;
      border-radius: 50%;
      width: 45px;
      height: 45px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 1.2rem;
      box-shadow: 0 2px 8px rgba(135,206,235,0.3);
    }
    
    .carousel-nav-btn:hover {
      background: #4682b4;
      transform: scale(1.1);
      box-shadow: 0 4px 12px rgba(70,130,180,0.4);
    }
    
    .carousel-nav-btn:disabled {
      background: #d3d3d3;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
      color: #888;
    }
    
    .carousel-info {
      text-align: center;
      color: #6c757d;
      font-size: 0.9rem;
      flex-grow: 1;
      margin: 0 1rem;
    }
    
    /* Responsive para móviles */
    @media (max-width: 768px) {
      .carousel-info {
        font-size: 0.8rem;
      }
      .carousel-nav-btn {
        width: 35px;
        height: 35px;
        font-size: 1rem;
      }
    }

    #itemsPerPageSelect { width: auto; display: inline-block; margin-left: 0.5rem; }

    /* Estilos para indicadores de stock actualizados */
    .stock-badge {
      font-size: 0.75rem;
      padding: 0.25rem 0.5rem;
      border-radius: 0.375rem;
      font-weight: 500;
    }
    .stock-disponible { 
      background-color: #d4edda; 
      color: #155724; 
      border: 1px solid #c3e6cb;
    }
    .stock-limitado { 
      background-color: #fff3cd; 
      color: #856404; 
      border: 1px solid #ffeaa7;
    }
    .stock-bajo { 
      background-color: #f8d7da; 
      color: #721c24; 
      border: 1px solid #f1aeb5;
    }
    .sin-stock { 
      background-color: #f1f3f4; 
      color: #6c757d; 
      border: 1px solid #dee2e6;
    }
    .sin-stock-permitido {
      background-color: #fff3cd; 
      color: #856404; 
      border: 1px solid #ffeaa7;
    }
    .stock-ilimitado {
      background-color: #e7f3ff; 
      color: #004085; 
      border: 1px solid #b3d7ff;
    }
    
    /* Productos sin stock (solo cuando NO se permite venta sin stock) */
    .producto-sin-stock {
      opacity: 0.6;
      filter: grayscale(0.3);
    }
    .producto-sin-stock .producto-card {
      cursor: not-allowed;
      border-color: #dee2e6;
    }
    .producto-sin-stock .producto-card:hover {
      transform: none;
      box-shadow: none;
    }
    
    /* Información de stock en detalle */
    .stock-info-detalle {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-top: 0.5rem;
      padding: 0.5rem;
      background-color: #f8f9fa;
      border-radius: 0.375rem;
      border-left: 4px solid;
    }
    .stock-info-detalle.disponible {
      border-left-color: #28a745;
    }
    .stock-info-detalle.limitado {
      border-left-color: #ffc107;
    }
    .stock-info-detalle.bajo {
      border-left-color: #dc3545;
    }
    .stock-info-detalle.sin-stock {
      border-left-color: #6c757d;
    }
    .stock-info-detalle.sin-stock-permitido {
      border-left-color: #ffc107;
    }
    .stock-info-detalle.ilimitado {
      border-left-color: #007bff;
    }
    
    /* Tabla de variantes con estados de stock */
    .table th, .table td {
      vertical-align: middle;
    }
    .table .stock-status {
      display: flex;
      align-items: center;
      gap: 0.25rem;
    }
    
    /* Input deshabilitado por stock */
    input:disabled {
      background-color: #f8f9fa;
      opacity: 0.6;
    }
    
    /* Botones deshabilitados por stock */
    .btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
    
    /* Alert para configuración de stock */
    .stock-config-alert {
      font-size: 0.875rem;
      padding: 0.5rem 0.75rem;
      margin-top: 0.5rem;
      border-radius: 0.375rem;
    }
    .stock-config-alert.no-control {
      background-color: #e7f3ff;
      color: #004085;
      border: 1px solid #b3d7ff;
    }
    .stock-config-alert.permite-sin-stock {
      background-color: #fff3cd;
      color: #856404;
      border: 1px solid #ffeaa7;
    }
  </style>
  @endpush

  <div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      {{-- Toolbar compacta (Cliente / Búsqueda / Categoría / Vista / Carrito) --}}
      <div class="bg-white shadow-sm rounded-lg mb-3 px-3 py-2 catalogo-toolbar">
        <div class="row align-items-center g-2">
          <div class="col-md-3">
            <a href="{{ route('catalogo') }}" class="text-decoration-none small">
              <i class="bi bi-arrow-left"></i> Cambiar cliente
            </a>
            <div class="text-muted small">
              Lista: <strong>{{ $cliente->listaPrecio?->nombre ?? '—' }}</strong>
            </div>
          </div>
          <div class="col-md-4">
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="text" class="form-control" id="busquedaProducto" placeholder="Buscar por nombre o referencia...">
            </div>
          </div>
          <div class="col-md-2">
            <select class="form-select form-select-sm" id="filtroCategoria" aria-label="Filtrar por categoría">
              <option value="">Todas las categorías</option>
              @foreach($categorias as $categoria)
                <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3 text-end d-flex justify-content-end gap-1">
            <button class="btn btn-outline-secondary btn-sm" id="btnToggleView" title="Cambiar vista">
              <i class="bi bi-view-list"></i>
            </button>
            <select class="form-select form-select-sm d-none" id="itemsPerPageSelect" style="width:auto;">
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3" selected>3</option>
              <option value="6">6</option>
              <option value="9">9</option>
              <option value="12">12</option>
            </select>
            <button class="btn btn-primary btn-sm position-relative" id="btnCarrito">
              <i class="bi bi-cart"></i>
              <span class="badge rounded-pill bg-danger cart-badge" id="cartCount" style="display:none;">0</span>
            </button>
          </div>
        </div>
      </div>

      {{-- Productos --}}
      <div class="bg-white shadow-sm rounded-lg overflow-hidden">
        <div class="p-4">
          <div id="productosContainer" class="row">
            <div class="col-12 text-center py-5">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando productos...</span>
              </div>
            </div>
          </div>
          <div id="paginacionContainer" class="mt-4"></div>
        </div>
      </div>
    </div>
  </div>

  {{-- Sidebar del Carrito --}}
<div id="cartSidebar">
  <div class="p-4 border-bottom d-flex justify-content-between align-items-center flex-shrink-0">
    <h5 class="mb-0">Carrito de Compras</h5>
    <button class="btn-close" id="closeCart"></button>
  </div>
  <div class="flex-grow-1 overflow-auto p-4">
    <div id="cartItems">
      <p class="text-muted text-center">El carrito está vacío</p>
    </div>
  </div>
  <div class="p-4 border-top flex-shrink-0">
    <div class="d-flex justify-content-between mb-3">
      <strong>Total:</strong>
      <strong id="cartTotal">$0.00</strong>
    </div>
    <button class="btn btn-success w-100" id="btnFinalizarSolicitud" disabled>
      <i class="bi bi-check-circle"></i> Finalizar Solicitud
    </button>
  </div>
</div>

  {{-- Modal Producto --}}
  <div class="modal fade" id="modalProducto" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalProductoTitle">Detalle del Producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="modalProductoContent">
          <div class="text-center"><div class="spinner-border" role="status"></div></div>
        </div>
      </div>
    </div>
  </div>

  {{-- Modal Confirmar --}}
  <div class="modal fade" id="modalConfirmarSolicitud" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Confirmar Solicitud de Cotización</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Notas adicionales (opcional)</label>
            <textarea class="form-control" id="notasSolicitud" rows="3"
                      placeholder="Ingrese cualquier comentario o requerimiento especial..."></textarea>
          </div>
          <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Al confirmar, se enviará la solicitud de cotización con los productos seleccionados.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" id="btnConfirmarSolicitud">
            <i class="bi bi-send"></i> Enviar Solicitud
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- Loading --}}
  <div class="loading-overlay" id="loadingOverlay" style="display:none;">
    <div class="text-center">
      <div class="spinner-border text-primary mb-3" role="status"></div>
      <p>Procesando solicitud...</p>
    </div>
  </div>

  @push('scripts')
  <script>
  $(function(){
    const clienteId    = {{ $cliente->id }};
    const enlaceToken  = null; // No hay enlace en flujo B
    const mostrarPrecios = true; // Siempre mostrar precios en flujo B
    const mostrarStock = true;   // Siempre mostrar stock en flujo B
    
    // Variables para el carrusel
    let viewType = 'grid';
    let itemsPerPage = 3;
    let carouselPage = 1;
    let totalCarouselPages = 1;
    let productosCarousel = [];
    
    let carrito = JSON.parse(localStorage.getItem('carrito_'+clienteId) || '[]')
                    .map(i=>({...i, precio: parseFloat(i.precio)||0}));
    let productosCargados = {};

    function actualizarCarrito(){
      let total=0, itemsHtml='';
      if(!carrito.length){
        itemsHtml = '<p class="text-muted text-center">El carrito está vacío</p>';
        $('#btnFinalizarSolicitud').prop('disabled',true);
      } else {
        carrito.forEach((item,i)=>{
          const precioUnit = parseFloat(item.precio)||0;
          const subtotal   = mostrarPrecios ? precioUnit*item.cantidad : 0;
          total += subtotal;
          itemsHtml += `
            <div class="card mb-2">
              <div class="card-body p-2">
                <div class="d-flex justify-content-between">
                  <div style="flex: 1;">
                    <h6 class="mb-0">${item.nombre}</h6>
                    <small class="text-muted">Ref: ${item.referencia}</small>
                    ${item.variante?`<br><small class="text-info">${item.variante}</small>`:''}
                    ${(mostrarPrecios && !isNaN(precioUnit))
                      ?`<br><small>${precioUnit.toFixed(2)} c/u ${item.unidad_venta ? `<span class="text-muted">Und/V: ${item.unidad_venta}</span>` : ''}</small>`
                      :''}
                  </div>
                  <button class="btn btn-sm btn-outline-danger" onclick="eliminarDelCarrito(${i})">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
                <div class="mt-2 d-flex align-items-center">
                  <button class="btn btn-sm btn-outline-secondary" onclick="cambiarCantidad(${i},-1)">-</button>
                  <input type="number" class="form-control form-control-sm mx-2 text-center"
                        style="width:60px" value="${item.cantidad}"
                        onchange="actualizarCantidad(${i},this.value)">
                  <button class="btn btn-sm btn-outline-secondary" onclick="cambiarCantidad(${i},1)">+</button>
                  ${mostrarPrecios
                    ?`<span class="ms-auto">${subtotal.toFixed(2)}</span>`
                    :''}
                </div>
              </div>
            </div>`;
        });
        $('#btnFinalizarSolicitud').prop('disabled',false);
      }
      $('#cartItems').html(itemsHtml);
      $('#cartTotal').text(mostrarPrecios? total.toFixed(2):'N/A');
      $('#cartCount').text(carrito.reduce((s,i)=>s+i.cantidad,0))
                    .toggle(!!carrito.length);
      localStorage.setItem('carrito_'+clienteId, JSON.stringify(carrito));
    }

    window.eliminarDelCarrito = i=>{
      carrito.splice(i,1); actualizarCarrito();
    };
    window.cambiarCantidad = (i,delta)=>{
      const n = carrito[i].cantidad+delta;
      if(n>0){ carrito[i].cantidad=n; actualizarCarrito(); }
    };
    window.actualizarCantidad = (i,val)=>{
      val = parseInt(val)||0;
      if(val>0){ carrito[i].cantidad=val; actualizarCarrito(); }
    };

    function agregarAlCarrito(producto,cantidad,variante=null){
      const precioRaw   = variante? variante.precio_final: producto.precio;
      const precioUnit = parseFloat(precioRaw)||0;
      const idx = carrito.findIndex(it=>
        it.producto_id===producto.id &&
        it.variante_id === (variante?.id||null)
      );
      if(idx > -1){
        carrito[idx].cantidad += cantidad;
      } else {
        carrito.push({
          producto_id: producto.id,
          variante_id: variante?.id||null,
          referencia: producto.referencia,
          nombre: producto.nombre,
          variante: variante? (variante.extension||'').trim():null,
          precio: precioUnit,
          unidad_venta: producto.unidad_venta || '', // Agregamos la unidad de venta
          cantidad
        });
      }
      actualizarCarrito();
    }

    window.agregarProductoSimple = id=>{
      const cnt = parseInt($('#cantidadProducto').val())||0;
      if(cnt>0){
        const prod = window.productoActual || productosCargados[id];
        
        // Verificar stock solo si se muestra Y se controla Y no permite sin stock
        if(mostrarStock && prod.stock_info && prod.stock_info.controla_stock && !prod.stock_info.permite_sin_stock) {
          if(!prod.stock_info.tiene_stock) {
            mostrarNotificacion('No hay stock disponible para este producto', 'warning');
            return;
          }
          
          if(cnt > prod.stock_info.cantidad_disponible) {
            mostrarNotificacion(`Cantidad solicitada (${cnt}) excede el stock disponible (${prod.stock_info.cantidad_disponible})`, 'warning');
            return;
          }
        }
        
        agregarAlCarrito(prod,cnt);
        $('#modalProducto').modal('hide');
        mostrarNotificacion('Producto agregado al carrito','success');
      }
    };

    // Funciones para toggle de vista
    function toggleView(){
      viewType = (viewType === 'grid' ? 'carousel' : 'grid');
      $('#itemsPerPageSelect').toggleClass('d-none', viewType !== 'carousel');
      $('#btnToggleView i').toggleClass('bi-view-list bi-grid-3x3-gap');
      $('#btnToggleView').contents().last()[0].textContent = viewType === 'carousel' ? ' Ver en cuadrícula' : ' Ver como carrusel';
      carouselPage = 1;
      cargarProductos(1);
    }

    $('#btnToggleView').click(toggleView);
    $('#itemsPerPageSelect').change(function(){
      itemsPerPage = parseInt($(this).val()) || 3;
      carouselPage = 1;
      totalCarouselPages = Math.ceil(productosCarousel.length / itemsPerPage) || 1;
      renderCarousel();
    });

function cargarProductos(page=1){
  $.post('{{route("catalogo.productos")}}',{
    _token:'{{csrf_token()}}',
    page, busqueda:$('#busquedaProducto').val(),
    categoria_id:$('#filtroCategoria').val(),
    cliente_id:clienteId, enlace_token:enlaceToken
  },resp=>{
    const prods = resp.productos.data;
    productosCarousel = prods;
    
    // Asegurarnos de que los productos cargados tengan la unidad_venta
    prods.forEach(p => {
      productosCargados[p.id] = p;
    });
    
    if (viewType === 'grid') {
      renderGrid(prods, resp);
    } else {
      totalCarouselPages = Math.ceil(prods.length / itemsPerPage) || 1;
      renderCarousel();
    }
  });
}

    function renderGrid(prods, resp) {
      $('#productosContainer').removeClass('productos-carousel').addClass('productos-grid');
      
      let html = !prods.length
        ?'<div class="col-12 text-center py-5"><p class="text-muted">No se encontraron productos</p></div>'
        : prods.map(p=>{
            productosCargados[p.id]=p;
            return buildCard(p, 'col-12 col-md-4 col-lg-3 col-xl-2');
          }).join('');

      $('#productosContainer').html(html);
      // Solo mostrar paginación si hay más de una página
      if(resp.productos.last_page > 1) {
        $('#paginacionContainer').html(buildPagination(resp));
      } else {
        $('#paginacionContainer').empty();
      }
    }

    function renderCarousel() {
      $('.carousel-pagination').remove();
      $('#productosContainer').removeClass('productos-grid').addClass('productos-carousel');
      
      totalCarouselPages = Math.ceil(productosCarousel.length / itemsPerPage) || 1;
      if (carouselPage > totalCarouselPages) {
        carouselPage = totalCarouselPages;
      }
      
      const start = (carouselPage - 1) * itemsPerPage;
      const pageItems = productosCarousel.slice(start, start + itemsPerPage);
      
      // Responsive columns para carrusel
      let colClass = 'col-12';
      if (itemsPerPage === 1) {
        colClass = 'col-12';
      } else if (itemsPerPage === 2) {
        colClass = 'col-12 col-sm-6';
      } else if (itemsPerPage === 3) {
        colClass = 'col-12 col-sm-6 col-md-4';
      } else if (itemsPerPage === 6) {
        colClass = 'col-12 col-sm-4 col-md-2';
      } else if (itemsPerPage === 9) {
        colClass = 'col-12 col-sm-4 col-md-3 col-lg-2';
      } else if (itemsPerPage === 12) {
        colClass = 'col-12 col-sm-4 col-md-3 col-lg-2 col-xl-1';
      }
      
      const html = pageItems.map(p => {
        productosCargados[p.id] = p;
        return buildCard(p, colClass);
      }).join('');
      
      $('#productosContainer').html(html);
      $('#productosContainer').before(buildCarouselNavigation());
      $('#productosContainer').after(buildCarouselNavigation());
      $('#paginacionContainer').empty(); // Limpiar paginación en modo carrusel
    }

    function buildCard(p, colClass = 'col-12 col-sm-4 col-md-3 col-lg-2 col-xl-2') {
      const img = p.imagen_principal
        ? `{{asset('')}}${p.imagen_principal.ruta_imagen}`
        : '{{asset("images/sin-imagen.png")}}';
      
      const raw = p.precio, num = parseFloat(raw);
      const precioTag = (mostrarPrecios && raw!=null && !isNaN(num))
        ?`<br><strong>${num.toFixed(2)}</strong>`:'';
      
      // Información de stock
      let stockTag = '';
      let cardClass = 'card producto-card h-100';
      let clickHandler = `onclick="verProducto(${p.id})"`;
      
      if (mostrarStock && p.stock_info) {
        const stock = p.stock_info;
        let badgeClass = 'stock-badge ';
        let icon = '';
        
        // Si no controla stock, siempre disponible
        if (!stock.controla_stock) {
          badgeClass += 'stock-ilimitado';
          icon = '<i class="bi bi-infinity"></i>';
          stockTag = `<br><span class="${badgeClass}">${icon} Stock ilimitado</span>`;
        } else {
          switch(stock.estado) {
            case 'disponible':
              badgeClass += 'stock-disponible';
              icon = '<i class="bi bi-check-circle"></i>';
              break;
            case 'stock_limitado':
              badgeClass += 'stock-limitado';
              icon = '<i class="bi bi-exclamation-triangle"></i>';
              break;
            case 'stock_bajo':
              badgeClass += 'stock-bajo';
              icon = '<i class="bi bi-exclamation-circle"></i>';
              break;
            case 'sin_stock':
              badgeClass += 'sin-stock';
              icon = '<i class="bi bi-x-circle"></i>';
              // Solo deshabilitar si no permite venta sin stock
              if (!stock.permite_sin_stock) {
                cardClass += ' producto-sin-stock';
                clickHandler = '';
              }
              break;
            case 'sin_stock_permitido':
              badgeClass += 'sin-stock-permitido';
              icon = '<i class="bi bi-exclamation-triangle"></i>';
              break;
          }
          
          stockTag = `<br><span class="${badgeClass}">${icon} ${stock.mensaje}</span>`;
        }
      }
      
      return `
        <div class="${colClass} mb-4">
          <div class="${cardClass}" ${clickHandler}>
            <div class="producto-imagen-container">
              <img src="${img}" class="producto-imagen" alt="${p.nombre}">
              <div class="producto-imagen-overlay"><i class="bi bi-zoom-in"></i></div>
            </div>
            <div class="card-body">
              <h6 class="card-title mb-1">${p.nombre}</h6>
              <small class="text-muted">Ref: ${p.referencia}</small>
            </div>
          </div>
        </div>`;
    }

    function buildPagination(resp) {
      let pgHtml='';

      return pgHtml;
    }

    function buildCarouselNavigation() {
      return `
        <div class="carousel-pagination">
          <button class="carousel-nav-btn" id="prevCarouselBtn" ${carouselPage <= 1 ? 'disabled' : ''}>
            <i class="bi bi-chevron-left"></i>
          </button>
          <div class="carousel-info">
            Página ${carouselPage} de ${totalCarouselPages}<br>
            <small>(${productosCarousel.length} productos total)</small>
          </div>
          <button class="carousel-nav-btn" id="nextCarouselBtn" ${carouselPage >= totalCarouselPages ? 'disabled' : ''}>
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>`;
    }

    // Event delegation para botones del carrusel
    $(document).on('click', '#prevCarouselBtn', function() {
      if (carouselPage > 1) {
        carouselPage--;
        renderCarousel();
      }
    });

    $(document).on('click', '#nextCarouselBtn', function() {
      if (carouselPage < totalCarouselPages) {
        carouselPage++;
        renderCarousel();
      }
    });

    window.verProducto = id=>{
      $('#modalProducto').modal('show');
      $('#modalProductoContent').html('<div class="text-center"><div class="spinner-border"></div></div>');
      $.get(`{{route("catalogo.producto.detalle","")}}/${id}`,{cliente_id:clienteId,enlace_token:enlaceToken},resp=>{
        const p=resp.producto;
        window.productoActual=p;
        let html='<div class="row">';
        
        // Imágenes
        html+='<div class="col-md-6">';
        if(p.imagenes?.length){
          html+='<div id="carouselProducto" class="carousel slide" data-bs-ride="carousel"><div class="carousel-inner">';
          p.imagenes.forEach((img,i)=>{
            html+=`<div class="carousel-item ${i===0?"active":""}"><img src="{{asset("")}}${img.ruta_imagen}" class="d-block w-100" style="height:400px;object-fit:contain;background-color:#ffffff;"></div>`;
          });
          html+='</div>';
          if(p.imagenes.length>1){
            html+=`<button class="carousel-control-prev" type="button" data-bs-target="#carouselProducto" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>`;
            html+=`<button class="carousel-control-next" type="button" data-bs-target="#carouselProducto" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>`;
          }
          html+='</div>';
        } else {
          html+='<img src="{{asset("images/sin-imagen.png")}}" class="img-fluid" style="object-fit:contain;background-color:#ffffff;">';
        }
        html+='</div>';

        // Info del producto
        html+='<div class="col-md-6">';
        html+=`<h4>${p.nombre}</h4>`;
        html+=`<p class="text-muted mb-2">Referencia: ${p.referencia}</p>`;
        if(p.descripcion) html+=`<p>${p.descripcion}</p>`;

        // Precio (con prefijo USD y 2 decimales, solo si tiene permiso)
        if(mostrarPrecios){
          const raw=p.precio, num=parseFloat(raw);
          if(raw!=null&&!isNaN(num)) html+=`<h5 class="text-primary mb-3">USD ${num.toFixed(2)}</h5>`;
        }

        // Unidad de venta
        if(p.unidad_venta) html+=`<p class="mb-1"><strong>Unidad de venta:</strong> ${p.unidad_venta}</p>`;

        // Inventario (solo si tiene permiso)
        if(mostrarStock && p.stock_info){
          const cant = p.stock_info.controla_stock
            ? (p.stock_info.cantidad_disponible ?? 0)
            : 'Ilimitado';
          html+=`<p class="mb-1"><strong>Inventario:</strong> ${cant}</p>`;
        }

        // Unidad de empaque
        if(p.unidad_empaque) html+=`<p class="mb-3"><strong>Unidad de empaque:</strong> ${p.unidad_empaque}</p>`;
        
        // Información de stock del producto principal
        if(mostrarStock && p.stock_info && !p.tiene_variantes) {
          const stock = p.stock_info;
          let stockClass = '';
          let stockIcon = '';
          let stockState = '';
          
          if (!stock.controla_stock) {
            stockClass = 'text-primary';
            stockIcon = 'bi-infinity';
            stockState = 'ilimitado';
            html += `<div class="stock-info-detalle ${stockState}">
              <i class="bi ${stockIcon} ${stockClass}"></i>
              <span class="${stockClass}">Stock ilimitado</span>
            </div>`;
          } else {
            switch(stock.estado) {
              case 'disponible':
                stockClass = 'text-success';
                stockIcon = 'bi-check-circle';
                stockState = 'disponible';
                break;
              case 'stock_limitado':
                stockClass = 'text-warning';
                stockIcon = 'bi-exclamation-triangle';
                stockState = 'limitado';
                break;
              case 'stock_bajo':
                stockClass = 'text-danger';
                stockIcon = 'bi-exclamation-circle';
                stockState = 'bajo';
                break;
              case 'sin_stock':
                stockClass = 'text-muted';
                stockIcon = 'bi-x-circle';
                stockState = 'sin-stock';
                break;
              case 'sin_stock_permitido':
                stockClass = 'text-warning';
                stockIcon = 'bi-x-circle';
                stockState = 'sin-stock-permitido';
                break;
            }
            
            html += `<div class="stock-info-detalle ${stockState}">
              <i class="bi ${stockIcon} ${stockClass}"></i>
              <span class="${stockClass}">${stock.mensaje}</span>
            </div>`;
          }
        }
        
        // Variantes o cantidad simple
        if(p.tiene_variantes&&p.variantes?.length){
          html+='<hr><h6 style="color:#0d6efd;">Solicitado acá:</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>SKU</th><th>Ext.</th>';
          if(mostrarStock) html+='<th>Stock</th>';
          html+='<th>Cantidad</th></tr></thead><tbody>';

          p.variantes.forEach((v,i)=>{
            html+='<tr>';
            html+=`<td><small>${v.sku||''}</small></td><td>${v.extension||'-'}</td>`;

            // Stock de variante (cantidad disponible)
            if(mostrarStock) {
              let stockTxt = '0';
              if(v.stock_info) {
                stockTxt = v.stock_info.controla_stock ? (v.stock_info.cantidad_disponible ?? 0) : 'Ilimitado';
              }
              html+=`<td>${stockTxt}</td>`;
            }

            // Campo cantidad - aplicar lógica de control de stock
            let maxCantidad = 999999;
            let inputDisabled = '';

            if(mostrarStock && v.stock_info && v.stock_info.controla_stock) {
              // Si controla stock pero no permite venta sin stock, limitar cantidad
              if(!v.stock_info.permite_sin_stock && !v.stock_info.tiene_stock) {
                maxCantidad = 0;
                inputDisabled = 'disabled';
              } else if(!v.stock_info.permite_sin_stock && v.stock_info.cantidad_disponible > 0) {
                maxCantidad = v.stock_info.cantidad_disponible;
              }
              // Si permite venta sin stock o no controla stock, no limitar
            }

            html+=`<td><input type="number" class="form-control form-control-sm variante-cantidad"
                      data-variante-index="${i}" min="0" max="${maxCantidad}" value="0" ${inputDisabled}></td>`;
            html+='</tr>';
          });

          html+='</tbody></table></div>';
          html+=`<button class="btn btn-primary w-100" onclick="agregarVariantesAlCarrito(${p.id})">Solicitar</button>`;
        } else {
          html+='<hr><div class="mb-3"><label class="form-label">Cantidad:</label>';
          
          // Determinar cantidad máxima disponible
          let maxCantidad = 999999;
          let inputDisabled = '';
          
          if(mostrarStock && p.stock_info && p.stock_info.controla_stock) {
            // Si controla stock pero no permite venta sin stock, limitar
            if(!p.stock_info.permite_sin_stock && !p.stock_info.tiene_stock) {
              maxCantidad = 0;
              inputDisabled = 'disabled';
            } else if(!p.stock_info.permite_sin_stock && p.stock_info.cantidad_disponible > 0) {
              maxCantidad = p.stock_info.cantidad_disponible;
            }
            // Si permite venta sin stock o no controla stock, no limitar
          }
          
          html+=`<input type="number" class="form-control" id="cantidadProducto" min="1" max="${maxCantidad}" value="1" ${inputDisabled}>`;
          html+='</div>';
          
          // Botón agregar - determinar si deshabilitar
          let btnDisabled = '';
          let btnText = 'Solicitar';
          
          if(mostrarStock && p.stock_info && p.stock_info.controla_stock) {
            if(!p.stock_info.permite_sin_stock && !p.stock_info.tiene_stock) {
              btnDisabled = 'disabled';
              btnText = 'Sin Stock';
            }
          }
          
          html+=`<button class="btn btn-primary w-100" onclick="agregarProductoSimple(${p.id})" ${btnDisabled}>${btnText}</button>`;
        }
        html+='</div></div>';
        $('#modalProductoContent').html(html);
      });
    };

    window.agregarVariantesAlCarrito = id=>{
      const prod = window.productoActual || productosCargados[id];
      let ok=false;
      $('.variante-cantidad').each(function(){
        const cnt=parseInt($(this).val())||0;
        if(cnt>0){
          const idx=$(this).data('variante-index'), v=prod.variantes[idx];
          
          // Verificar stock solo si se muestra Y se controla Y no permite sin stock
          if(mostrarStock && v.stock_info && v.stock_info.controla_stock && !v.stock_info.permite_sin_stock) {
            if(!v.stock_info.tiene_stock) {
              mostrarNotificacion(`No hay stock disponible para la variante: ${v.nombre_variante}`, 'warning');
              return;
            }
            
            if(cnt > v.stock_info.cantidad_disponible) {
              mostrarNotificacion(`Cantidad solicitada (${cnt}) excede el stock disponible (${v.stock_info.cantidad_disponible}) para: ${v.nombre_variante}`, 'warning');
              return;
            }
          }
          
          agregarAlCarrito(prod,cnt,v);
          ok=true;
        }
      });
      if(ok){
        $('#modalProducto').modal('hide');
        mostrarNotificacion('Variantes agregadas al carrito','success');
      } else {
        mostrarNotificacion('Seleccione al menos una cantidad válida','warning');
      }
    };

    $('#btnFinalizarSolicitud').click(()=>{
      if(!carrito.length) return;
      $('#modalConfirmarSolicitud').modal('show');
    });
    
    $('#btnConfirmarSolicitud').click(()=>{
      const notas = $('#notasSolicitud').val();
      $('#loadingOverlay').show();
      const items = carrito.map(i=>({producto_id:i.producto_id,variante_id:i.variante_id,cantidad:i.cantidad}));
      $.post('{{route("catalogo.solicitud.guardar")}}',{
        _token:'{{csrf_token()}}',cliente_id:clienteId,
        enlace_token:enlaceToken,items,notas_cliente:notas
      },r=>{
        $('#loadingOverlay').hide();
        $('#modalConfirmarSolicitud').modal('hide');
        if(r.success){
          carrito=[]; actualizarCarrito();
          const msg=`<div class="alert alert-success alert-dismissible fade show">
            <h5>¡Solicitud Enviada!</h5><p>Su solicitud ha sido registrada.</p><hr>
            <p>Número de solicitud: <strong>${r.numero_solicitud}</strong></p>
            <button class="btn-close" data-bs-dismiss="alert"></button>
          </div>`;
          $('.max-w-7xl').prepend(msg);
          window.scrollTo(0,0);
        }
      }).fail(xhr=>{
        $('#loadingOverlay').hide();
        mostrarNotificacion(xhr.responseJSON?.mensaje||'Error al procesar','danger');
      });
    });

    $('#btnCarrito').click(()=>$('#cartSidebar').addClass('show'));
    $('#closeCart').click(()=>$('#cartSidebar').removeClass('show'));
    $('#busquedaProducto').on('keyup',debounce(()=>cargarProductos(1),500));
    $('#filtroCategoria').change(()=>cargarProductos(1));

    function mostrarNotificacion(msg,t='info'){
      const $t = $(`<div class="toast" role="alert" style="position:fixed;top:20px;right:20px;z-index:1060">
        <div class="toast-body bg-${t} text-white">${msg}</div>
      </div>`);
      $('body').append($t);
      new bootstrap.Toast($t[0]).show();
      setTimeout(()=>$t.remove(),3000);
    }
    
    function debounce(fn,ms){
      let t;
      return function(...a){
        clearTimeout(t);
        t=setTimeout(()=>fn.apply(this,a),ms);
      };
    }

    actualizarCarrito();
    cargarProductos();
  });
  </script>
  @endpush
</x-app-layout>