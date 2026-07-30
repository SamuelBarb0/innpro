<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\LeadsController;
use App\Http\Controllers\LlamadasController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\SalesController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CiudadController;
use App\Http\Controllers\CategoriasController;
use App\Http\Controllers\ProductosController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\ActualizacionPreciosController;
use App\Http\Controllers\OrdenServicioController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::redirect('/', '/login'); // 302 por defecto

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/dashboard/exportar', [DashboardController::class, 'exportar'])->middleware(['auth', 'verified'])->name('dashboard.exportar');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/usuarios', [UsuariosController::class, 'index'])->name('usuarios');
    Route::get('/importar_usuarios', [UsuariosController::class, 'importar_usuarios'])->name('importar_usuarios');
    Route::get('/usuarios_form/{user?}', [UsuariosController::class, 'form'])->name('usuarios.form');
    Route::post('/usuarios/guardar', [UsuariosController::class, 'guardar'])->name('usuarios.guardar');
    Route::post('/usuarios/{user}/toggle-activo', [UsuariosController::class, 'toggleActivo'])->name('usuarios.toggle-activo');
    Route::delete('/usuarios/{user}', [UsuariosController::class, 'eliminar'])->name('usuarios.eliminar');

    // Configuración de la empresa dueña (datos del encabezado del PDF). Solo admin (validado en el controlador).
    Route::get('/empresa', [EmpresaController::class, 'edit'])->name('empresa.edit');
    Route::post('/empresa', [EmpresaController::class, 'update'])->name('empresa.update');

Route::get('ajax/ciudades', [CiudadController::class,'byDepartamento'])
     ->name('ajax.ciudades');

//Clientes
    // Listado & AJAX
    Route::get('clientes', [ClientesController::class, 'index'])
        ->name('clientes');

    // Formulario (crear / editar)
    Route::get('clientes/form/{cliente?}', [ClientesController::class, 'form'])
        ->name('clientes.form');

    // Guardar
    Route::post('clientes/guardar', [ClientesController::class, 'guardar'])
        ->name('clientes.guardar');

    // Toggle activo / eliminar
    Route::post('clientes/{cliente}/toggle-activo', [ClientesController::class, 'toggleActivo'])
        ->name('clientes.toggle-activo');
    Route::delete('clientes/{cliente}', [ClientesController::class, 'eliminar'])
        ->name('clientes.eliminar');

    // Sedes / proyectos por cliente
    Route::get('clientes/{cliente}/sucursales', [App\Http\Controllers\ClienteSucursalController::class, 'index'])
        ->name('clientes.sucursales');
    Route::post('clientes/{cliente}/sucursales', [App\Http\Controllers\ClienteSucursalController::class, 'guardar'])
        ->name('clientes.sucursales.guardar');
    Route::post('clientes/{cliente}/sucursales/{sucursal}/toggle', [App\Http\Controllers\ClienteSucursalController::class, 'toggleActivo'])
        ->name('clientes.sucursales.toggle');
    Route::get('clientes/{cliente}/sucursales/{sucursal}/stock', [App\Http\Controllers\ClienteSucursalController::class, 'stock'])
        ->name('clientes.sucursales.stock');
    Route::post('clientes/{cliente}/sucursales/{sucursal}/stock', [App\Http\Controllers\ClienteSucursalController::class, 'guardarStock'])
        ->name('clientes.sucursales.stock.guardar');
    Route::delete('clientes/{cliente}/sucursales/{sucursal}', [App\Http\Controllers\ClienteSucursalController::class, 'eliminar'])
        ->name('clientes.sucursales.eliminar');

            // Listado & AJAX
    Route::get('categorias', [CategoriasController::class, 'index'])
         ->name('categorias');

    // Formulario (nuevo / editar)
    Route::get('categorias/form/{categoria?}', [CategoriasController::class, 'form'])
         ->name('categorias.form');

    // Guardar (crear / actualizar)
    Route::post('categorias/guardar', [CategoriasController::class, 'guardar'])
         ->name('categorias.guardar');

    // Toggle activo / Eliminar
    Route::post('categorias/{categoria}/toggle-activo', [CategoriasController::class, 'toggleActivo'])
         ->name('categorias.toggle-activo');
    Route::delete('categorias/{categoria}', [CategoriasController::class, 'eliminar'])
         ->name('categorias.eliminar');
// Rutas de Productos - versión simplificada
Route::prefix('productos')->middleware('auth')->group(function () {
    Route::get('/', [ProductosController::class, 'index'])->name('productos');
    Route::get('/form/{producto?}', [ProductosController::class, 'form'])->name('productos.form');
    Route::post('/guardar', [ProductosController::class, 'guardar'])->name('productos.guardar');
    Route::post('/{producto}/toggle-activo', [ProductosController::class, 'toggleActivo'])->name('productos.toggle-activo');
    Route::delete('/{producto}', [ProductosController::class, 'eliminar'])->name('productos.eliminar');
    Route::get('/importar', [ProductosController::class, 'mostrarImportar'])->name('productos.importar');
    Route::get('/importar/plantilla', [ProductosController::class, 'descargarPlantilla'])->name('productos.plantilla');
    Route::post('/importar-excel', [ProductosController::class, 'importarExcel'])->name('productos.importar-excel');
    Route::get('/{producto}/variantes-ajax', [ProductosController::class, 'variantesAjax'])->name('productos.variantes-ajax');
    Route::get('/{producto}/imagenes-ajax', [ProductosController::class, 'imagenesAjax'])->name('productos.imagenes-ajax');
    Route::get('/{producto}/precios-ajax', [ProductosController::class, 'preciosAjax'])->name('productos.precios-ajax');
});
Route::get('actualizaciones/{id}/descargar', 
    [ActualizacionPreciosController::class, 'descargarArchivoActualizacion']
)->name('actualizaciones.descargar');


});
// Rutas del Catálogo Interactivo
// Flujo A: Acceso público por token
// Agregar estas rutas en routes/web.php

// Módulo de Enlaces de Acceso (autenticado)
Route::middleware(['auth'])->group(function () {
    // Enlaces temporales
    Route::get('/enlaces', [App\Http\Controllers\EnlacesController::class, 'index'])->name('enlaces');
    Route::get('/enlaces/crear', [App\Http\Controllers\EnlacesController::class, 'crear'])->name('enlaces.crear');
    Route::post('/enlaces/guardar', [App\Http\Controllers\EnlacesController::class, 'guardar'])->name('enlaces.guardar');
    Route::get('/enlaces/{enlace}/detalle', [App\Http\Controllers\EnlacesController::class, 'detalle'])->name('enlaces.detalle');
    Route::post('/enlaces/{enlace}/cambiar-estado', [App\Http\Controllers\EnlacesController::class, 'cambiarEstado'])->name('enlaces.cambiar-estado');
});

// Catálogo público con token (sin autenticación)
Route::get('/catalogo/{token}', [App\Http\Controllers\CatalogoController::class, 'mostrarPorToken'])->name('catalogo.token');

// Flujo B: Acceso autenticado (vendedor/admin)
Route::middleware(['auth'])->group(function () {
    Route::get('/catalogo', [CatalogoController::class, 'index'])->name('catalogo');
    Route::post('/catalogo/cliente', [CatalogoController::class, 'mostrarParaCliente'])->name('catalogo.cliente');
    Route::post('/catalogo/cliente-temporal', [CatalogoController::class, 'crearClienteTemporal'])->name('catalogo.cliente.temporal');
});

// Rutas AJAX del catálogo (pueden ser públicas o autenticadas)
Route::post('/catalogo/productos', [CatalogoController::class, 'obtenerProductos'])->name('catalogo.productos');
Route::get('/catalogo/producto/{producto}', [CatalogoController::class, 'detalleProducto'])->name('catalogo.producto.detalle');
Route::post('/catalogo/solicitud', [CatalogoController::class, 'guardarSolicitud'])->name('catalogo.solicitud.guardar');

// Rutas de Gestión de Solicitudes
Route::middleware(['auth'])->group(function () {
    Route::get('/solicitudes', [SolicitudController::class, 'index'])->name('solicitudes');
    Route::get('/solicitudes/pendientes-count', [SolicitudController::class, 'pendientesCount'])->name('solicitudes.pendientes-count');
    Route::get('/solicitudes/{solicitud}/detalle', [SolicitudController::class, 'detalle'])->name('solicitudes.detalle');
    Route::post('/solicitudes/{solicitud}/aplicar', [SolicitudController::class, 'aplicar'])->name('solicitudes.aplicar');
});


// Rutas de Stock
Route::prefix('stock')->name('stock.')->middleware('auth')->group(function () {
    // Vistas principales
    Route::get('/', [App\Http\Controllers\StockController::class, 'index'])->name('index');
    Route::get('/dashboard', [App\Http\Controllers\StockController::class, 'dashboard'])->name('dashboard');

    // Importación masiva desde Excel
    Route::post('/importar-excel', [App\Http\Controllers\StockController::class, 'importarExcel'])->name('importar-excel');
    Route::get('/plantilla-importacion', [App\Http\Controllers\StockController::class, 'descargarPlantilla'])->name('plantilla-importacion');
    
    // Operaciones de stock
    Route::post('/entrada', [App\Http\Controllers\StockController::class, 'entrada'])->name('entrada');
    Route::post('/salida', [App\Http\Controllers\StockController::class, 'salida'])->name('salida');
    Route::post('/ajuste', [App\Http\Controllers\StockController::class, 'ajuste'])->name('ajuste');
    Route::post('/configurar', [App\Http\Controllers\StockController::class, 'configurar'])->name('configurar');
    
    // Consultas AJAX
    Route::get('/productos-json', [App\Http\Controllers\StockController::class, 'productosJson'])->name('productos-json');
    Route::get('/{id}/obtener', [App\Http\Controllers\StockController::class, 'obtenerStock'])->name('obtener');
    Route::get('/historial', [App\Http\Controllers\StockController::class, 'historial'])->name('historial');
    
    // Reportes
    Route::get('/reporte-movimientos', [App\Http\Controllers\StockController::class, 'reporteMovimientos'])->name('reporte-movimientos');
    
    // Importación/Exportación
    Route::post('/importar', [App\Http\Controllers\StockController::class, 'importar'])->name('importar');
    Route::get('/exportar', [App\Http\Controllers\StockController::class, 'exportar'])->name('exportar');
    
    // Inicializar stock
    Route::post('/inicializar-todos', [App\Http\Controllers\StockController::class, 'inicializarTodos'])->name('inicializar-todos');
});

// Rutas autenticadas: stock-ajax de productos y descargas de solicitudes
Route::middleware('auth')->group(function () {
    // AJAX para ver stock desde productos
    Route::get('/productos/{producto}/stock-ajax', [App\Http\Controllers\ProductosController::class, 'stockAjax'])->name('productos.stock-ajax');

    // Solicitudes (PDF / Excel)
    Route::get('/solicitudes/{solicitud}/pdf', [SolicitudController::class, 'descargarPdf'])->name('solicitudes.pdf');
    Route::get('/solicitudes/exportar-excel', [SolicitudController::class, 'exportarExcel'])->name('solicitudes.exportar-excel');
});
Route::middleware(['auth'])->group(function () {
    // ... otras rutas existentes ...
    
    // Actualización de precios
    Route::post('/productos/actualizar-precios-excel', [ProductosController::class, 'actualizarPreciosExcel'])->name('productos.actualizar-precios-excel');
    Route::get('/productos/historial-precios', [ActualizacionPreciosController::class, 'historial'])->name('productos.historial-precios');
    Route::get('/productos/actualizacion-precios/{id}', [ActualizacionPreciosController::class, 'verDetalle'])->name('productos.actualizacion-precios.detalle');
    
    // Rutas para descargar plantillas
    Route::get('/productos/descargar-plantilla-csv', [ActualizacionPreciosController::class, 'descargarPlantillaCsv'])->name('productos.descargar-plantilla-csv');
    Route::get('/productos/descargar-plantilla-excel', [ActualizacionPreciosController::class, 'descargarPlantillaExcel'])->name('productos.descargar-plantilla-excel');
});
// ===== Seguimiento público de orden de servicio (sin autenticación) =====
Route::get('/seguimiento/{token}', [OrdenServicioController::class, 'seguimientoPublico'])->name('servicio.seguimiento');
Route::post('/seguimiento/{token}/firmar', [OrdenServicioController::class, 'firmarPublico'])->name('servicio.seguimiento.firmar');

// ===== Módulo de Servicio Técnico (Innpro) =====
Route::middleware('auth')->prefix('servicio')->name('servicio.')->group(function () {
    Route::get('/', [OrdenServicioController::class, 'index'])->name('index');
    Route::get('/form/{orden?}', [OrdenServicioController::class, 'form'])->name('form');
    Route::post('/guardar', [OrdenServicioController::class, 'guardar'])->name('guardar');
    Route::post('/cliente-temporal', [OrdenServicioController::class, 'crearClienteTemporal'])->name('cliente.temporal');
    Route::get('/{orden}/detalle', [OrdenServicioController::class, 'detalle'])->name('detalle');
    Route::get('/{orden}/pdf', [OrdenServicioController::class, 'pdf'])->name('pdf');
    Route::post('/{orden}/actualizar', [OrdenServicioController::class, 'actualizar'])->name('actualizar');

    // B5 — Ítems (equipos / repuestos)
    Route::post('/{orden}/items', [OrdenServicioController::class, 'agregarItem'])->name('items.agregar');
    Route::delete('/{orden}/items/{item}', [OrdenServicioController::class, 'eliminarItem'])->name('items.eliminar');

    // B4 — Bitácora
    Route::post('/{orden}/bitacora', [OrdenServicioController::class, 'agregarBitacora'])->name('bitacora.agregar');
    Route::delete('/{orden}/bitacora/{entrada}', [OrdenServicioController::class, 'eliminarBitacora'])->name('bitacora.eliminar');
    Route::post('/{orden}/bitacora-visible', [OrdenServicioController::class, 'toggleBitacoraVisible'])->name('bitacora.visible');

    // B3 — Imágenes / evidencia
    Route::post('/{orden}/imagenes', [OrdenServicioController::class, 'subirImagen'])->name('imagenes.subir');
    Route::delete('/{orden}/imagenes/{imagen}', [OrdenServicioController::class, 'eliminarImagen'])->name('imagenes.eliminar');

    // Colector de firmas (técnico / cliente)
    Route::post('/{orden}/firmar/{tipo}', [OrdenServicioController::class, 'firmar'])->name('firmar');
    Route::delete('/{orden}/firmar/{tipo}', [OrdenServicioController::class, 'quitarFirma'])->name('firmar.quitar');
});

require __DIR__.'/auth.php';
