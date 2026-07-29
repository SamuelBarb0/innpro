<x-app-layout>
  <x-slot name="header">
    {{ $producto->exists ? 'Editar Producto' : 'Nuevo Producto' }}
  </x-slot>

  <div class="container py-4">
    {{-- Mostrar errores de validación general --}}
    @if ($errors->any())
      <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <strong>Por favor corrija los siguientes errores:</strong>
        <ul class="mb-0 mt-2">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

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

    <form method="POST" action="{{ route('productos.guardar') }}" enctype="multipart/form-data" id="productoForm">
      @csrf
      <input type="hidden" name="id" value="{{ old('id',$producto->id) }}">
      
      {{-- Información Básica --}}
      <div class="card shadow mb-4">
        <div class="card-header">
          <h5 class="mb-0">Información Básica</h5>
        </div>
        <div class="card-body">
          <div class="row">
            {{-- Referencia --}}
            <div class="col-md-4 mb-3">
              <label class="form-label">Referencia <span class="text-danger">*</span></label>
              <input name="referencia" type="text"
                     class="form-control @error('referencia') is-invalid @enderror"
                     value="{{ old('referencia',$producto->referencia) }}"
                     required>
              @error('referencia') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Nombre --}}
            <div class="col-md-8 mb-3">
              <label class="form-label">Nombre del Producto <span class="text-danger">*</span></label>
              <input name="nombre" type="text"
                     class="form-control @error('nombre') is-invalid @enderror"
                     value="{{ old('nombre',$producto->nombre) }}"
                     required>
              @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Descripción --}}
            <div class="col-md-12 mb-3">
              <label class="form-label">Descripción</label>
              <textarea name="descripcion" rows="3"
                        class="form-control @error('descripcion') is-invalid @enderror">{{ old('descripcion',$producto->descripcion) }}</textarea>
              @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Unidad de Venta --}}
            <div class="col-md-3 mb-3">
              <label class="form-label">Unidad de Venta <span class="text-danger">*</span></label>
              <input name="unidad_venta" type="text"
                     class="form-control @error('unidad_venta') is-invalid @enderror"
                     value="{{ old('unidad_venta',$producto->unidad_venta) }}"
                     placeholder="Ej: Unidad, Caja"
                     required>
              @error('unidad_venta') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Unidad de Empaque --}}
            <div class="col-md-3 mb-3">
              <label class="form-label">Unidad de Empaque <span class="text-danger">*</span></label>
              <input name="unidad_empaque" type="text"
                     class="form-control @error('unidad_empaque') is-invalid @enderror"
                     value="{{ old('unidad_empaque',$producto->unidad_empaque) }}"
                     placeholder="Ej: Caja, Pallet"
                     required>
              @error('unidad_empaque') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Peso por paca --}}
            <div class="col-md-3 mb-3">
              <label class="form-label">Peso por paca (kg)</label>
              <input name="peso_paca" type="number" step="0.001" min="0"
                     class="form-control @error('peso_paca') is-invalid @enderror"
                     value="{{ old('peso_paca',$producto->peso_paca) }}"
                     placeholder="Ej: 12.500">
              @error('peso_paca') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Cubicaje (volumen) por paca --}}
            <div class="col-md-3 mb-3">
              <label class="form-label">Cubicaje por paca (m³)</label>
              <input name="cubicaje_paca" type="number" step="0.0001" min="0"
                     class="form-control @error('cubicaje_paca') is-invalid @enderror"
                     value="{{ old('cubicaje_paca',$producto->cubicaje_paca) }}"
                     placeholder="Ej: 0.0450">
              @error('cubicaje_paca') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Unidades por paca --}}
            <div class="col-md-3 mb-3">
              <label class="form-label">Unidades por paca</label>
              <input name="unidades_por_paca" type="number" step="0.001" min="0"
                     class="form-control @error('unidades_por_paca') is-invalid @enderror"
                     value="{{ old('unidades_por_paca',$producto->unidades_por_paca) }}"
                     placeholder="Ej: 12">
              <small class="text-muted">Cuántas unidades de venta trae una paca. Se usa para calcular peso/volumen en la cotización.</small>
              @error('unidades_por_paca') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Código de barras --}}
            <div class="col-md-3 mb-3">
              <label class="form-label">Código de barras</label>
              <input name="codigo_barras" type="text" maxlength="100"
                     class="form-control @error('codigo_barras') is-invalid @enderror"
                     value="{{ old('codigo_barras',$producto->codigo_barras) }}"
                     placeholder="Ej: 7701234567890">
              @error('codigo_barras') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- ¿Maneja extensión? --}}
            <div class="col-md-3 mb-3">
              <label class="form-label">¿Maneja extensión?</label>
              <div class="form-check mt-2">
                <input type="hidden" name="tiene_extension" value="0">
                <input class="form-check-input" type="checkbox"
                       name="tiene_extension" id="tiene_extension"
                       value="1"
                       {{ old('tiene_extension', $producto->tiene_extension) ? 'checked' : '' }}>
                <label class="form-check-label" for="tiene_extension">
                  Sí, maneja extensión
                </label>
              </div>
            </div>

            {{-- Categoría: oculta a petición de Innpro (no clasifican productos por categoría).
                 Si no se envía, el controlador asigna "Sin categoría". Para retomarla,
                 basta con volver a mostrar este bloque. --}}

            {{-- Tiene Variantes --}}
            <div class="col-md-12 mb-3">
              <div class="form-check">
                <input type="hidden" name="tiene_variantes" value="0">
                <input class="form-check-input" type="checkbox"
                       name="tiene_variantes" id="tiene_variantes"
                       value="1"
                       {{ old('tiene_variantes',$producto->tiene_variantes) ? 'checked' : '' }}>
                <label class="form-check-label" for="tiene_variantes">
                  Este producto tiene variantes (tallas/colores)
                </label>
                <small class="text-muted d-block">
                  Si marca esta opción, podrá configurar las variantes del producto a continuación.
                </small>
              </div>
            </div>

            @if($producto->exists)
              {{-- Activo --}}
              <div class="col-md-12 mb-3">
                <div class="form-check">
                  <input type="hidden" name="activo" value="0">
                  <input class="form-check-input" type="checkbox"
                         name="activo" id="activo"
                         value="1"
                         {{ old('activo', $producto->activo) ? 'checked' : '' }}>
                  <label class="form-check-label" for="activo">
                    Producto activo
                  </label>
                  <small class="text-muted d-block">
                    Si está desactivado, el producto no aparecerá en el catálogo.
                  </small>
                </div>
              </div>
            @endif
          </div>
        </div>
      </div>
{{-- Control de Stock --}}
<div class="card shadow mb-4">
  <div class="card-header">
    <h5 class="mb-0">Control de Stock</h5>
  </div>
  <div class="card-body">
    <div class="row">
      {{-- Controlar Stock --}}
      <div class="col-md-6 mb-3">
        <div class="form-check">
          <input type="hidden" name="controlar_stock" value="0">
          <input class="form-check-input" type="checkbox" 
                 name="controlar_stock" id="controlar_stock"
                 value="1"
                 {{ old('controlar_stock', $producto->controlar_stock ?? true) ? 'checked' : '' }}>
          <label class="form-check-label" for="controlar_stock">
            Controlar stock de este producto
          </label>
          <small class="text-muted d-block">
            Si desmarca esta opción, no se controlará el stock del producto.
          </small>
        </div>
      </div>

      {{-- Permitir venta sin stock --}}
      <div class="col-md-6 mb-3">
        <div class="form-check">
          <input type="hidden" name="permitir_venta_sin_stock" value="0">
          <input class="form-check-input" type="checkbox" 
                 name="permitir_venta_sin_stock" id="permitir_venta_sin_stock"
                 value="1"
                 {{ old('permitir_venta_sin_stock', $producto->permitir_venta_sin_stock) ? 'checked' : '' }}>
          <label class="form-check-label" for="permitir_venta_sin_stock">
            Permitir venta sin stock disponible
          </label>
          <small class="text-muted d-block">
            Permite realizar ventas aunque no haya stock disponible.
          </small>
        </div>
      </div>
    </div>

    {{-- Campos de stock para productos sin variantes --}}
    <div id="stockSimpleSection" style="display: none;">
      <hr class="my-3">
      <h6>Configuración de Stock</h6>
      
      <div class="row">
        {{-- Stock Inicial (solo para productos nuevos) --}}
        @if(!$producto->exists)
          <div class="col-md-3 mb-3">
            <label class="form-label">Stock Inicial</label>
            <input type="number" name="stock_inicial" 
                   class="form-control @error('stock_inicial') is-invalid @enderror"
                   value="{{ old('stock_inicial', 0) }}"
                   min="0">
            @error('stock_inicial') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <small class="text-muted">Cantidad inicial en inventario</small>
          </div>
        @else
          <div class="col-md-3 mb-3">
            <label class="form-label">Stock Actual</label>
            @php
              $stockActual = $producto->stockPrincipal ? $producto->stockPrincipal->cantidad_disponible : 0;
            @endphp
            <input type="text" class="form-control" value="{{ $stockActual }}" readonly>
            <small class="text-muted">Use el módulo de stock para modificar</small>
          </div>
        @endif

        {{-- Stock Mínimo --}}
        <div class="col-md-3 mb-3">
          <label class="form-label">Stock Mínimo</label>
          <input type="number" name="stock_minimo" 
                 class="form-control @error('stock_minimo') is-invalid @enderror"
                 value="{{ old('stock_minimo', $producto->stockPrincipal->stock_minimo ?? 0) }}"
                 min="0">
          @error('stock_minimo') <div class="invalid-feedback">{{ $message }}</div> @enderror
          <small class="text-muted">Alerta cuando baje de este nivel</small>
        </div>

        {{-- Stock Máximo --}}
        <div class="col-md-3 mb-3">
          <label class="form-label">Stock Máximo</label>
          <input type="number" name="stock_maximo" 
                 class="form-control @error('stock_maximo') is-invalid @enderror"
                 value="{{ old('stock_maximo', $producto->stockPrincipal->stock_maximo ?? '') }}"
                 min="0">
          @error('stock_maximo') <div class="invalid-feedback">{{ $message }}</div> @enderror
          <small class="text-muted">Opcional: límite máximo de stock</small>
        </div>

        {{-- Ubicación --}}
        <div class="col-md-3 mb-3">
          <label class="form-label">Ubicación en Bodega</label>
          <input type="text" name="ubicacion_stock" 
                 class="form-control @error('ubicacion_stock') is-invalid @enderror"
                 value="{{ old('ubicacion_stock', $producto->stockPrincipal->ubicacion ?? '') }}"
                 placeholder="Ej: A-1-3">
          @error('ubicacion_stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
      </div>
    </div>

    {{-- Información de stock para productos con variantes --}}
    <div id="stockVariantesInfo" style="display: none;">
      <hr class="my-3">
      <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> 
        <strong>Producto con variantes:</strong> El stock se gestiona individualmente para cada variante.
        Configure el stock de cada variante después de crear el producto, desde el módulo de gestión de stock.
      </div>
    </div>
  </div>
</div>
      {{-- Variantes --}}
      <div class="card shadow mb-4" id="variantesSection" style="display: none;">
        <div class="card-header">
          <h5 class="mb-0">Variantes del Producto</h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-12">
              <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="addVariante">
                <i class="bi bi-plus-circle"></i> Agregar Variante
              </button>
              
              <div id="variantesContainer">
                @if($producto->exists && $producto->variantes->count() > 0)
                  @foreach($producto->variantes as $index => $variante)
                    <div class="variante-row mb-3">
                      <div class="row align-items-end">
                        <div class="col-md-5">
                          <label class="form-label">Extensión</label>
                          <input type="text" name="variantes[{{ $index }}][extension]"
                                 class="form-control" value="{{ $variante->extension }}"
                                 placeholder="Ej: Rojo, Floral, Talla M">
                        </div>
                        <div class="col-md-5">
                          <label class="form-label">SKU</label>
                          <input type="text" name="variantes[{{ $index }}][sku]"
                                 class="form-control" value="{{ $variante->sku }}"
                                 placeholder="Se genera automáticamente">
                        </div>
                        <div class="col-md-2">
                          <button type="button" class="btn btn-danger btn-sm removeVariante">
                            <i class="bi bi-trash"></i> Eliminar
                          </button>
                        </div>
                      </div>
                    </div>
                  @endforeach
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Imágenes --}}
      <div class="card shadow mb-4">
        <div class="card-header">
          <h5 class="mb-0">Imágenes del Producto</h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-12">
              <div class="mb-3">
                <label class="form-label">Agregar Imágenes</label>
                <div class="input-group">
                  <input type="file" class="form-control" 
                         accept="image/jpeg,image/jpg,image/png,image/webp" 
                         multiple="multiple" id="imagenesInput">
                  <button class="btn btn-outline-secondary" type="button" id="btnAgregarImagenes">
                    <i class="bi bi-plus-circle"></i> Agregar
                  </button>
                </div>
                <small class="text-muted d-block mt-1">
                  <i class="bi bi-info-circle"></i> Seleccione una o varias imágenes y haga clic en "Agregar".
                  <br>Formatos permitidos: JPG, PNG, WebP. Tamaño máximo: 2MB por imagen.
                </small>
              </div>
              
              {{-- Contador de imágenes seleccionadas --}}
              <div id="imagenesCounter" class="alert alert-info d-none mb-3">
                <i class="bi bi-images"></i> <span id="imagenesCount">0</span> imagen(es) agregada(s)
              </div>
              
              {{-- Vista previa de imágenes nuevas --}}
              <div id="imagenesPreview" class="row mb-3"></div>
              
              {{-- Input oculto para mantener los archivos --}}
              <div id="hiddenInputsContainer"></div>
              
              {{-- Imágenes existentes --}}
              @if($producto->exists && $producto->imagenes->count() > 0)
                <h6>Imágenes Actuales:</h6>
                <div class="row">
                  @foreach($producto->imagenes as $imagen)
                    <div class="col-md-3 mb-3">
                      <div class="card">
                        <img src="{{ asset($imagen->ruta_imagen) }}" 
                             class="card-img-top" 
                             alt="{{ $imagen->texto_alternativo }}"
                             style="height: 150px; object-fit: cover;">
                        <div class="card-body p-2">
                          <div class="form-check mb-2">
                            <input class="form-check-input imagen-principal-existente" type="radio" 
                                   name="imagen_principal_existente" value="{{ $imagen->id }}" 
                                   id="principal_{{ $imagen->id }}"
                                   {{ $imagen->es_principal ? 'checked' : '' }}>
                            <label class="form-check-label" for="principal_{{ $imagen->id }}">
                              Principal
                            </label>
                          </div>
                          <div class="form-check">
                            <input type="checkbox" name="eliminar_imagenes[]" 
                                   value="{{ $imagen->id }}" 
                                   class="form-check-input"
                                   id="eliminar_{{ $imagen->id }}">
                            <label class="form-check-label text-danger" for="eliminar_{{ $imagen->id }}">
                              Eliminar
                            </label>
                          </div>
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>

      {{-- Precios --}}
      <div class="card shadow mb-4">
        <div class="card-header">
          <h5 class="mb-0">Precios por Lista</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Lista de Precios</th>
                  <th>Código</th>
                  <th width="200">Precio</th>
                </tr>
              </thead>
              <tbody>
                @foreach($listas as $lista)
                  <tr>
                    <td>{{ $lista->nombre }}</td>
                    <td><code>{{ $lista->codigo }}</code></td>
                    <td>
                      <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" 
                               name="precios[{{ $lista->id }}]" 
                               class="form-control"
                               step="0.01"
                               min="0"
                               value="{{ $producto->exists ? $producto->precios->where('lista_precio_id', $lista->id)->first()?->precio : '' }}">
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <small class="text-muted">Deje el campo vacío para no asignar precio a esa lista.</small>
        </div>
      </div>

      {{-- Botones --}}
      <div class="d-flex justify-content-between">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-save"></i> {{ $producto->exists ? 'Actualizar' : 'Guardar' }} Producto
        </button>
        <a href="{{ route('productos') }}" class="btn btn-outline-secondary">
          <i class="bi bi-x-circle"></i> Cancelar
        </a>
      </div>
    </form>
  </div>

  @push('styles')
  <style>
    .removeImage:hover {
      opacity: 0.8;
    }
    
    .card img {
      cursor: pointer;
    }
    
    .text-truncate {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    
    #imagenesInput {
      cursor: pointer;
    }
  </style>
  @endpush

  @push('scripts')
  <script>
  $(document).ready(function() {
    // Función para mostrar/ocultar campos de stock
    function toggleStockFields() {
      const controlarStock = $('#controlar_stock').is(':checked');
      const tieneVariantes = $('#tiene_variantes').is(':checked');
      
      if (controlarStock) {
        if (tieneVariantes) {
          $('#stockSimpleSection').hide();
          $('#stockVariantesInfo').show();
        } else {
          $('#stockSimpleSection').show();
          $('#stockVariantesInfo').hide();
        }
      } else {
        $('#stockSimpleSection').hide();
        $('#stockVariantesInfo').hide();
      }
    }
    
    // Eventos
    $('#controlar_stock, #tiene_variantes').change(toggleStockFields);
    
    // Ejecutar al cargar
    toggleStockFields();
  });
</script>
  <script>
    $(document).ready(function() {
      // Mostrar/ocultar sección de variantes
      function toggleVariantes() {
        if ($('#tiene_variantes').is(':checked')) {
          $('#variantesSection').slideDown();
        } else {
          $('#variantesSection').slideUp();
        }
      }
      
      $('#tiene_variantes').change(toggleVariantes);
      toggleVariantes(); // Ejecutar al cargar
      
      // Contador para variantes
      let varianteIndex = {{ $producto->exists ? $producto->variantes->count() : 0 }};
      
      // Agregar nueva variante
      $('#addVariante').click(function() {
        const template = `
          <div class="variante-row mb-3">
            <div class="row align-items-end">
              <div class="col-md-5">
                <label class="form-label">Extensión</label>
                <input type="text" name="variantes[${varianteIndex}][extension]"
                       class="form-control" placeholder="Ej: Rojo, Floral, Talla M">
              </div>
              <div class="col-md-5">
                <label class="form-label">SKU</label>
                <input type="text" name="variantes[${varianteIndex}][sku]"
                       class="form-control" placeholder="Se genera automáticamente">
              </div>
              <div class="col-md-2">
                <button type="button" class="btn btn-danger btn-sm removeVariante">
                  <i class="bi bi-trash"></i> Eliminar
                </button>
              </div>
            </div>
          </div>
        `;
        
        $('#variantesContainer').append(template);
        varianteIndex++;
      });
      
      // Eliminar variante
      $(document).on('click', '.removeVariante', function() {
        $(this).closest('.variante-row').remove();
      });
      
      // Manejo de imágenes con acumulación
      let imagenesFiles = [];
      let imagenesDataTransfer = new DataTransfer();
      
      // Función para actualizar el contador
      function actualizarContadorImagenes() {
        const count = imagenesFiles.length;
        if (count > 0) {
          $('#imagenesCount').text(count);
          $('#imagenesCounter').removeClass('d-none');
        } else {
          $('#imagenesCounter').addClass('d-none');
        }
      }
      
      // Función para renderizar las imágenes
      function renderizarImagenes() {
        $('#imagenesPreview').empty();
        
        if (imagenesFiles.length === 0) {
          return;
        }
        
        $('#imagenesPreview').append('<h6 class="col-12 mb-2">Imágenes a subir:</h6>');
        
        imagenesFiles.forEach((fileInfo, index) => {
          const preview = `
            <div class="col-md-3 mb-3" data-index="${index}">
              <div class="card">
                <img src="${fileInfo.preview}" class="card-img-top" 
                     style="height: 150px; object-fit: cover;">
                <div class="card-body p-2">
                  <div class="form-check mb-2">
                    <input class="form-check-input imagen-principal-nueva" type="radio" 
                           name="imagen_principal_nueva" value="${index}" 
                           id="principal_nueva_${index}" ${index === 0 ? 'checked' : ''}>
                    <label class="form-check-label" for="principal_nueva_${index}">
                      Principal
                    </label>
                  </div>
                  <small class="text-muted d-block text-truncate">${fileInfo.file.name}</small>
                  <small class="text-muted d-block">${(fileInfo.file.size / 1024).toFixed(2)} KB</small>
                  <button type="button" class="btn btn-danger btn-sm w-100 mt-2 removeImage" data-index="${index}">
                    <i class="bi bi-trash"></i> Eliminar
                  </button>
                </div>
              </div>
            </div>
          `;
          $('#imagenesPreview').append(preview);
        });
        
        actualizarContadorImagenes();
      }
      
      // Agregar imágenes
      $('#btnAgregarImagenes, #imagenesInput').on('click change', function(e) {
        if (e.type === 'click' && e.target.id === 'btnAgregarImagenes') {
          // Si es el botón, procesar los archivos del input
          const input = document.getElementById('imagenesInput');
          if (!input.files || input.files.length === 0) {
            alert('Por favor seleccione al menos una imagen');
            return;
          }
          procesarArchivos(input.files);
          input.value = ''; // Limpiar el input
        } else if (e.type === 'change') {
          // Si es el input, esperar al botón
          e.preventDefault();
        }
      });
      
      // Procesar archivos seleccionados
      function procesarArchivos(files) {
        for (let i = 0; i < files.length; i++) {
          const file = files[i];
          
          // Validar tipo
          if (!file.type.startsWith('image/')) {
            alert(`El archivo ${file.name} no es una imagen válida.`);
            continue;
          }
          
          // Validar tamaño
          if (file.size > 2 * 1024 * 1024) {
            alert(`El archivo ${file.name} supera los 2MB permitidos.`);
            continue;
          }
          
          // Verificar si ya existe
          const existe = imagenesFiles.some(f => 
            f.file.name === file.name && f.file.size === file.size
          );
          
          if (existe) {
            alert(`La imagen ${file.name} ya fue agregada.`);
            continue;
          }
          
          // Leer y agregar la imagen
          const reader = new FileReader();
          reader.onload = function(e) {
            imagenesFiles.push({
              file: file,
              preview: e.target.result
            });
            
            // Agregar al DataTransfer
            imagenesDataTransfer.items.add(file);
            
            // Actualizar el input oculto
            actualizarInputOculto();
            
            // Renderizar
            renderizarImagenes();
          };
          reader.readAsDataURL(file);
        }
      }
      
      // Actualizar input oculto con los archivos
      function actualizarInputOculto() {
        // Crear un nuevo input file con los archivos acumulados
        const container = document.getElementById('hiddenInputsContainer');
        container.innerHTML = '';
        
        const newInput = document.createElement('input');
        newInput.type = 'file';
        newInput.name = 'imagenes[]';
        newInput.multiple = true;
        newInput.style.display = 'none';
        newInput.files = imagenesDataTransfer.files;
        
        container.appendChild(newInput);
      }
      
      // Eliminar imagen individual
      $(document).on('click', '.removeImage', function() {
        const index = $(this).data('index');
        
        // Eliminar del array
        imagenesFiles.splice(index, 1);
        
        // Reconstruir DataTransfer
        imagenesDataTransfer = new DataTransfer();
        imagenesFiles.forEach(fileInfo => {
          imagenesDataTransfer.items.add(fileInfo.file);
        });
        
        // Actualizar input oculto
        actualizarInputOculto();
        
        // Renderizar
        renderizarImagenes();
      });
      
      // Validación del formulario
      $('#productoForm').submit(function(e) {
        let isValid = true;
        
        // Validar que si tiene variantes, al menos tenga una
        if ($('#tiene_variantes').is(':checked')) {
          const variantes = $('#variantesContainer .variante-row');
          if (variantes.length === 0) {
            e.preventDefault();
            alert('Debe agregar al menos una variante si el producto tiene variantes.');
            isValid = false;
          }
        }
        
        return isValid;
      });
    });
  </script>
  @endpush
</x-app-layout>