<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\ClienteSucursal;
use App\Models\ListaPrecio;
use App\Models\MovimientoStock;
use App\Models\OrdenServicio;
use App\Models\OrdenServicioBitacora;
use App\Models\OrdenServicioItem;
use App\Models\PrecioProducto;
use App\Models\Producto;
use App\Models\SolicitudCotizacion;
use App\Models\StockProducto;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Datos de demostración para enseñar la plataforma funcionando.
 *
 * No es relleno: cada bloque existe para que una pantalla concreta tenga algo
 * que mostrar. Los clientes se reparten en meses para que las métricas tengan
 * tendencia; hay un cliente con tres sedes en tres ciudades porque ese es el
 * caso que planteó Jorge; el stock queda repartido entre bodega general y sedes
 * para que se vea que no se mezclan; y las órdenes cubren varios estados para
 * que el tablero no salga de un solo color.
 *
 *   php artisan db:seed --class=DemoInnproSeeder
 *
 * Es idempotente: correrlo dos veces no duplica nada.
 */
class DemoInnproSeeder extends Seeder
{
    /**
     * Los NITs de los clientes sembrados. Son la llave para borrarlos después
     * (`LimpiarDemoInnproSeeder`): no se marcan los textos con «[demo]» porque
     * el motivo de un movimiento y la descripción de una orden se muestran en
     * pantalla, y una demostración al cliente no debería llevar andamios a la
     * vista.
     */
    public const NITS = ['900123456-1', '901987654-3', '830456789-2', '860112233-4', '805334455-6'];

    public const PROSPECTOS = ['Ferretería El Tornillo', 'Conjunto Altos del Bosque'];

    public function run(): void
    {
        if (! $this->permitido()) {
            $this->command?->error('ABORTADO: esto es para demostración, no para producción.');
            $this->command?->warn('En producción mezclaría clientes inventados con los reales del cliente.');
            $this->command?->line('Si de verdad hace falta, correr con DEMO_FORZAR=1.');

            return;
        }

        $usuarios = $this->usuarios();
        $productos = $this->productos();
        $listas = $this->listasDePrecios($productos);
        $clientes = $this->clientes($listas, $usuarios['vendedor']);
        $sedes = $this->sedes($clientes);

        $this->stock($productos, $clientes, $sedes, $usuarios['admin']);
        $this->ordenes($clientes, $sedes, $productos, $usuarios);
        $this->solicitudes($clientes);

        $this->resumen();
    }

    /**
     * Producción queda fuera salvo orden expresa: sembrar clientes inventados
     * en la base del cliente ensucia sus métricas y su pipeline, y separarlos
     * después cuesta más que volver a sembrarlos aquí.
     */
    private function permitido(): bool
    {
        return ! app()->environment('production') || env('DEMO_FORZAR') === '1';
    }

    /** @return array{admin:User,vendedor:User,tecnico:User} */
    private function usuarios(): array
    {
        foreach (['admin', 'vendedor', 'tecnico'] as $rol) {
            Role::findOrCreate($rol);
        }

        $crear = function (string $email, string $nombre, string $rol): User {
            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $nombre, 'password' => Hash::make('password'), 'email_verified_at' => now()],
            );

            if (! $user->hasRole($rol)) {
                $user->assignRole($rol);
            }

            return $user;
        };

        return [
            'admin' => $crear('admin@portfolio.test', 'Administrador', 'admin'),
            'vendedor' => $crear('vendedor@innpro.test', 'Carolina Ruiz', 'vendedor'),
            'tecnico' => $crear('tecnico@innpro.test', 'Luis Ramírez', 'tecnico'),
        ];
    }

    /**
     * Catálogo de seguridad electrónica.
     *
     * Las referencias son descripciones largas a propósito: así es como las
     * tiene Innpro de verdad, y es justo lo que rompía el importador antes del
     * arreglo del 30 de julio. Sirven de prueba viva de que ya se toleran.
     *
     * Es público para que `LimpiarDemoInnproSeeder` sepa de qué productos es el
     * stock que tiene que retirar.
     *
     * @return array<string,array{0:string,1:string,2:string,3:string}>
     */
    public static function catalogo(): array
    {
        return [
            'cam-bullet' => ['CÁMARA BULLET IP 4MP LENTE 2.8MM VISIÓN NOCTURNA 30M', 'Cámara bullet IP 4MP', 'Unidad', 'Caja'],
            'cam-domo' => ['CÁMARA DOMO IP 4MP ANTIVANDÁLICA PoE INTERIOR/EXTERIOR', 'Cámara domo IP 4MP', 'Unidad', 'Caja'],
            'cam-ptz' => ['CÁMARA PTZ IP 2MP ZOOM ÓPTICO 25X SEGUIMIENTO AUTOMÁTICO', 'Cámara PTZ 25X', 'Unidad', 'Caja'],
            'nvr-16' => ['GRABADOR NVR 16 CANALES 4K CON 2 BAHÍAS DE DISCO', 'NVR 16 canales 4K', 'Unidad', 'Caja'],
            'disco-4t' => ['DISCO DURO 4TB PARA VIDEOVIGILANCIA 24/7', 'Disco 4TB videovigilancia', 'Unidad', 'Caja'],
            'switch-poe' => ['SWITCH POE 8 PUERTOS GIGABIT 120W GESTIONABLE', 'Switch PoE 8 puertos', 'Unidad', 'Caja'],
            'lector-facial' => ['LECTOR DE RECONOCIMIENTO FACIAL Y HUELLA CON CONTROL DE ASISTENCIA', 'Lector facial + huella', 'Unidad', 'Caja'],
            'cerradura' => ['CERRADURA ELECTROMAGNÉTICA 600 LBS CON SENSOR DE ESTADO', 'Cerradura magnética 600lb', 'Unidad', 'Caja'],
            'boton-salida' => ['BOTÓN DE SALIDA NO TÁCTIL CON INDICADOR LED', 'Botón de salida', 'Unidad', 'Bolsa'],
            'panel-incendio' => ['PANEL DE DETECCIÓN DE INCENDIO DIRECCIONABLE 2 LAZOS', 'Panel incendio 2 lazos', 'Unidad', 'Caja'],
            'detector-humo' => ['DETECTOR DE HUMO FOTOELÉCTRICO DIRECCIONABLE CON BASE', 'Detector de humo', 'Unidad', 'Caja'],
            'sirena' => ['SIRENA ESTROBOSCÓPICA DE PARED PARA EVACUACIÓN', 'Sirena estroboscópica', 'Unidad', 'Caja'],
            'cable-utp' => ['CABLE UTP CATEGORÍA 6 EXTERIOR X 305 METROS', 'Cable UTP Cat6 exterior', 'Rollo', 'Rollo'],
            'ups' => ['UPS 1500VA ONLINE PARA RACK DE COMUNICACIONES', 'UPS 1500VA', 'Unidad', 'Caja'],
        ];
    }

    /** @return array<string,Producto> */
    private function productos(): array
    {
        $categoria = Categoria::firstOrCreate(
            ['nombre' => 'Seguridad electrónica'],
            ['activo' => true],
        );

        $productos = [];

        foreach (self::catalogo() as $clave => [$referencia, $nombre, $unidadVenta, $unidadEmpaque]) {
            $productos[$clave] = Producto::firstOrCreate(
                ['referencia' => $referencia],
                [
                    'nombre' => $nombre,
                    'descripcion' => $nombre.' para proyectos de seguridad electrónica.',
                    'unidad_venta' => $unidadVenta,
                    'unidad_empaque' => $unidadEmpaque,
                    'categoria_id' => $categoria->id,
                    'activo' => true,
                    'tiene_variantes' => false,
                    // Se controla el stock PERO se permite cotizar sin él, que
                    // es el arreglo del 30-jul: Innpro pide contra proyecto, así
                    // que un cero en bodega no debe bloquear la cotización.
                    'controlar_stock' => true,
                    'permitir_venta_sin_stock' => true,
                ],
            );
        }

        return $productos;
    }

    /**
     * Dos listas para que se vea de qué sirve el módulo: la general y una
     * negociada para un cliente concreto, que es «la lista por cliente» del
     * pedido 10.
     *
     * @param  array<string,Producto>  $productos
     * @return array<string,ListaPrecio>
     */
    private function listasDePrecios(array $productos): array
    {
        $general = ListaPrecio::firstOrCreate(
            ['codigo' => 'general'],
            ['nombre' => 'Precio general', 'descripcion' => 'Lista base del catálogo.', 'activo' => true, 'orden' => 1],
        );

        $proyecto = ListaPrecio::firstOrCreate(
            ['codigo' => 'andina'],
            ['nombre' => 'Proyecto Andina', 'descripcion' => 'Precios negociados para Constructora Andina.', 'activo' => true, 'orden' => 2],
        );

        $base = [
            'cam-bullet' => 320000, 'cam-domo' => 345000, 'cam-ptz' => 2450000,
            'nvr-16' => 1780000, 'disco-4t' => 640000, 'switch-poe' => 890000,
            'lector-facial' => 1250000, 'cerradura' => 285000, 'boton-salida' => 62000,
            'panel-incendio' => 4300000, 'detector-humo' => 148000, 'sirena' => 195000,
            'cable-utp' => 780000, 'ups' => 2100000,
        ];

        foreach ($base as $clave => $precio) {
            PrecioProducto::firstOrCreate(
                ['producto_id' => $productos[$clave]->id, 'lista_precio_id' => $general->id],
                ['precio' => $precio, 'activo' => true],
            );

            // La lista de proyecto va un 12% abajo: el descuento por volumen
            // que justifica tener una lista propia.
            PrecioProducto::firstOrCreate(
                ['producto_id' => $productos[$clave]->id, 'lista_precio_id' => $proyecto->id],
                ['precio' => round($precio * 0.88, -2), 'activo' => true],
            );
        }

        return ['general' => $general, 'proyecto' => $proyecto];
    }

    /**
     * Clientes repartidos en el tiempo.
     *
     * El `created_at` se fuerza hacia atrás porque la tarjeta de clientes
     * nuevos compara contra el periodo anterior y la gráfica es mensual: si
     * todos nacieran hoy, las métricas saldrían planas y no se vería nada.
     *
     * @param  array<string,ListaPrecio>  $listas
     * @return array<string,Cliente>
     */
    private function clientes(array $listas, User $vendedor): array
    {
        $definicion = [
            'andina' => ['Constructora Andina S.A.S.', 'Jorge Herrera', '900123456-1', 'Bogotá', 'proyecto', 4],
            'centro' => ['Centro Comercial Plaza Mayor', 'Marcela Ospina', '901987654-3', 'Medellín', 'general', 3],
            'bodegas' => ['Bodegas del Norte Ltda.', 'Andrés Camacho', '830456789-2', 'Bogotá', 'general', 2],
            'clinica' => ['Clínica San Rafael', 'Patricia Gómez', '860112233-4', 'Cali', 'general', 1],
            'colegio' => ['Colegio Santa María', 'Ricardo Peña', '805334455-6', 'Bogotá', 'general', 0],
        ];

        $clientes = [];

        foreach ($definicion as $clave => [$empresa, $contacto, $nit, $ciudad, $lista, $mesesAtras]) {
            $cliente = Cliente::firstOrCreate(
                ['numero_identificacion' => $nit],
                [
                    'nombre_empresa' => $empresa,
                    'nombre_contacto' => $contacto,
                    'email' => strtolower(str_replace(' ', '', explode(' ', $contacto)[0])).'@'.\Illuminate\Support\Str::slug($empresa).'.test',
                    'telefono' => '60'.random_int(1, 8).' '.random_int(200, 899).' '.random_int(1000, 9999),
                    'pais' => 'Colombia',
                    'ciudad' => $ciudad,
                    'vendedor_id' => $vendedor->id,
                    'lista_precio_id' => $listas[$lista === 'proyecto' ? 'proyecto' : 'general']->id,
                    'activo' => true,
                    'es_temporal' => false,
                ],
            );

            $fecha = now()->subMonths($mesesAtras)->startOfMonth()->addDays(random_int(2, 24));
            $cliente->forceFill(['created_at' => $fecha, 'updated_at' => $fecha])->save();

            $clientes[$clave] = $cliente;
        }

        // Prospectos: solo nombre, sin NIT ni correo. Demuestran el pedido 8 y,
        // de paso, que varios NULL conviven bajo el índice único del NIT.
        foreach (['Ferretería El Tornillo', 'Conjunto Altos del Bosque'] as $i => $nombre) {
            $prospecto = Cliente::firstOrCreate(
                ['nombre_empresa' => $nombre, 'es_temporal' => true],
                [
                    'nombre_contacto' => $nombre,
                    'pais' => 'Colombia',
                    'vendedor_id' => $vendedor->id,
                    'lista_precio_id' => $listas['general']->id,
                    'activo' => true,
                ],
            );

            $fecha = now()->subDays(($i + 1) * 6);
            $prospecto->forceFill(['created_at' => $fecha, 'updated_at' => $fecha])->save();

            $clientes['prospecto'.$i] = $prospecto;
        }

        return $clientes;
    }

    /**
     * El caso exacto que planteó Jorge: un cliente con obra en tres ciudades a
     * la vez, cada una un proyecto distinto.
     *
     * @param  array<string,Cliente>  $clientes
     * @return array<string,ClienteSucursal>
     */
    private function sedes(array $clientes): array
    {
        $definicion = [
            'popayan' => ['andina', 'Obra Popayán', 'Popayán', 'Calle 5 # 12-30', 'Residente de obra'],
            'cali' => ['andina', 'Obra Cali', 'Cali', 'Av. 6N # 28-15', 'Coordinador técnico'],
            'medellin' => ['andina', 'Obra Medellín', 'Medellín', 'Carrera 43A # 18-40', 'Jefe de proyecto'],
            'plaza' => ['centro', 'Sede Principal', 'Medellín', 'Cra. 51 # 52-20', 'Jefe de seguridad'],
            'bodega1' => ['bodegas', 'Bodega Fontibón', 'Bogotá', 'Calle 17 # 96-50', 'Almacenista'],
        ];

        $sedes = [];

        foreach ($definicion as $clave => [$cliente, $nombre, $ciudad, $direccion, $contacto]) {
            $sedes[$clave] = ClienteSucursal::firstOrCreate(
                ['cliente_id' => $clientes[$cliente]->id, 'nombre' => $nombre],
                [
                    'ciudad' => $ciudad,
                    'direccion' => $direccion,
                    'contacto' => $contacto,
                    'telefono' => '3'.random_int(10, 20).' '.random_int(200, 899).' '.random_int(1000, 9999),
                    'activo' => true,
                ],
            );
        }

        return $sedes;
    }

    /**
     * Existencias en bodega general Y por sede, para que se vea de un vistazo
     * lo que costó construir: que no se mezclan.
     *
     * @param  array<string,Producto>  $productos
     * @param  array<string,Cliente>  $clientes
     * @param  array<string,ClienteSucursal>  $sedes
     */
    private function stock(array $productos, array $clientes, array $sedes, User $usuario): void
    {
        // El stock es una SECUENCIA de movimientos, no un estado: volver a
        // sembrarlo sumaría otra vez las mismas entradas y duplicaría tanto las
        // existencias como el histórico. Por eso se salta entero en vez de
        // intentar que cada movimiento sea idempotente por su cuenta.
        if (StockProducto::whereNotNull('sucursal_id')->exists()) {
            $this->command?->line('  (el stock de demostración ya estaba sembrado; no se repite)');

            return;
        }

        // Bodega general: cliente y sucursal en NULL, que es lo que significaba
        // el stock antes de que existieran las sedes.
        $general = ['cam-bullet' => 24, 'cam-domo' => 18, 'cable-utp' => 12, 'switch-poe' => 6, 'boton-salida' => 40];

        foreach ($general as $clave => $cantidad) {
            $this->movimiento($productos[$clave], null, null, $cantidad, 'Inventario inicial de bodega', $usuario);
        }

        // Recepciones por proyecto: lo que de verdad hace Innpro.
        $porSede = [
            'popayan' => ['cam-bullet' => 16, 'nvr-16' => 1, 'disco-4t' => 2, 'cable-utp' => 4],
            'cali' => ['cam-domo' => 22, 'nvr-16' => 2, 'switch-poe' => 3, 'ups' => 1],
            'medellin' => ['lector-facial' => 4, 'cerradura' => 4, 'boton-salida' => 4],
            'plaza' => ['panel-incendio' => 1, 'detector-humo' => 45, 'sirena' => 12],
            'bodega1' => ['cam-ptz' => 2, 'cam-bullet' => 8],
        ];

        foreach ($porSede as $claveSede => $items) {
            $sede = $sedes[$claveSede];

            foreach ($items as $claveProducto => $cantidad) {
                $this->movimiento(
                    $productos[$claveProducto],
                    $sede->cliente_id,
                    $sede->id,
                    $cantidad,
                    'Recepción de remisión para '.$sede->nombre,
                    $usuario,
                );
            }
        }

        // Una salida, para que el histórico no sea solo entradas: se instalaron
        // 6 de las 16 cámaras que llegaron a Popayán.
        $this->movimiento($productos['cam-bullet'], $sedes['popayan']->cliente_id, $sedes['popayan']->id, -6, 'Instaladas en torre 1', $usuario);
    }

    /** Crea o ajusta la existencia y deja el movimiento, como lo hace la pantalla real. */
    private function movimiento(Producto $producto, ?int $clienteId, ?int $sucursalId, int $delta, string $motivo, User $usuario): void
    {
        $stock = StockProducto::firstOrNew([
            'producto_id' => $producto->id,
            'variante_producto_id' => null,
            'cliente_id' => $clienteId,
            'sucursal_id' => $sucursalId,
        ]);

        $anterior = (int) ($stock->cantidad_disponible ?? 0);
        $nuevo = max(0, $anterior + $delta);

        if ($anterior === $nuevo) {
            return;
        }

        $stock->cantidad_disponible = $nuevo;
        $stock->cantidad_reservada = $stock->cantidad_reservada ?? 0;
        $stock->save();

        MovimientoStock::create([
            'producto_id' => $producto->id,
            'variante_producto_id' => null,
            'cliente_id' => $clienteId,
            'sucursal_id' => $sucursalId,
            'tipo_movimiento' => $delta > 0 ? 'entrada' : 'salida',
            'cantidad' => abs($delta),
            'stock_anterior' => $anterior,
            'stock_nuevo' => $nuevo,
            'origen' => 'otro',
            'motivo' => $motivo,
            'usuario_id' => $usuario->id,
        ]);
    }

    /**
     * Órdenes en varios estados y fechas.
     *
     * Reparte estados a propósito: con todas en «recibida» el tablero de
     * servicio y el donut del dashboard salen de un solo color y no se entiende
     * para qué sirven.
     *
     * @param  array<string,Cliente>  $clientes
     * @param  array<string,ClienteSucursal>  $sedes
     * @param  array<string,Producto>  $productos
     * @param  array{admin:User,vendedor:User,tecnico:User}  $usuarios
     */
    private function ordenes(array $clientes, array $sedes, array $productos, array $usuarios): void
    {
        $definicion = [
            ['andina', 'popayan', 'Instalación de CCTV torre 1', 'finalizada', 'alta', 38, 850000, true],
            ['andina', 'cali', 'Montaje de NVR y switch en cuarto técnico', 'en_proceso', 'media', 12, 620000, true],
            ['andina', 'medellin', 'Control de acceso peatonal', 'recibida', 'media', 4, 0, false],
            ['centro', 'plaza', 'Mantenimiento preventivo detección de incendio', 'facturado', 'baja', 60, 1200000, true],
            ['bodegas', 'bodega1', 'Cámara PTZ fuera de servicio', 'espera_repuestos', 'urgente', 9, 380000, false],
            ['clinica', null, 'Revisión de sistema de evacuación', 'en_diagnostico', 'alta', 6, 0, false],
            ['colegio', null, 'Garantía: domo con humedad interna', 'garantia', 'media', 21, 0, true],
        ];

        foreach ($definicion as [$claveCliente, $claveSede, $titulo, $estado, $prioridad, $diasAtras, $manoObra, $conBitacora]) {
            $cliente = $clientes[$claveCliente];

            if (OrdenServicio::where('titulo', $titulo)->exists()) {
                continue;
            }

            $ingreso = now()->subDays($diasAtras);

            $orden = OrdenServicio::create([
                'numero' => OrdenServicio::generarNumero(),
                'cliente_id' => $cliente->id,
                'sucursal_id' => $claveSede ? $sedes[$claveSede]->id : null,
                'tecnico_id' => $usuarios['tecnico']->id,
                'creado_por' => $usuarios['admin']->id,
                'titulo' => $titulo,
                'descripcion_problema' => 'Solicitud del cliente registrada en la visita.',
                'diagnostico' => in_array($estado, ['recibida'], true) ? null : 'Revisión en sitio realizada; se documenta el alcance y los materiales.',
                'estado' => $estado,
                'prioridad' => $prioridad,
                'costo_mano_obra' => $manoObra,
                'fecha_ingreso' => $ingreso,
                'fecha_cierre' => in_array($estado, ['finalizada', 'facturado', 'garantia'], true) ? $ingreso->copy()->addDays(3) : null,
                // Visible para el cliente solo en algunas: el interruptor existe
                // justamente para decidirlo por orden.
                'bitacora_visible_cliente' => $conBitacora,
                'observaciones' => null,
            ]);

            $orden->forceFill(['created_at' => $ingreso, 'updated_at' => $ingreso])->save();

            OrdenServicioItem::create([
                'orden_servicio_id' => $orden->id,
                'producto_id' => $productos['cam-bullet']->id,
                'tipo' => 'equipo',
                'descripcion' => 'Cámara bullet IP 4MP',
                'cantidad' => 4,
                'precio_unitario' => 320000,
            ]);

            OrdenServicioItem::create([
                'orden_servicio_id' => $orden->id,
                'producto_id' => $productos['cable-utp']->id,
                'tipo' => 'repuesto',
                'descripcion' => 'Cable UTP Cat6 exterior',
                'cantidad' => 1,
                'precio_unitario' => 780000,
            ]);

            if ($conBitacora) {
                foreach ([['Visita inicial y levantamiento en sitio.', 3.5], ['Tendido de canalización y cableado.', 6.0], ['Configuración y pruebas con el cliente.', 2.5]] as $i => [$texto, $horas]) {
                    $entrada = OrdenServicioBitacora::create([
                        'orden_servicio_id' => $orden->id,
                        'user_id' => $usuarios['tecnico']->id,
                        'descripcion' => $texto,
                        'horas_trabajadas' => $horas,
                        'observaciones' => null,
                    ]);

                    $cuando = $ingreso->copy()->addDays($i);
                    $entrada->forceFill(['created_at' => $cuando, 'updated_at' => $cuando])->save();
                }
            }
        }
    }

    /**
     * Solicitudes de cotización repartidas en semanas, para que el dashboard
     * comercial y el badge de pendientes tengan qué mostrar.
     *
     * @param  array<string,Cliente>  $clientes
     */
    private function solicitudes(array $clientes): void
    {
        $definicion = [
            ['andina', 'pendiente', 12500000, 3],
            ['centro', 'pendiente', 4800000, 8],
            ['bodegas', 'aplicada', 2650000, 19],
            ['clinica', 'aplicada', 7300000, 34],
            ['colegio', 'pendiente', 1950000, 2],
        ];

        foreach ($definicion as [$claveCliente, $estado, $monto, $diasAtras]) {
            $cliente = $clientes[$claveCliente];

            if (SolicitudCotizacion::where('cliente_id', $cliente->id)->where('monto_total', $monto)->exists()) {
                continue;
            }

            $solicitud = SolicitudCotizacion::create([
                'cliente_id' => $cliente->id,
                'estado' => $estado,
                'monto_total' => $monto,
                'notas_cliente' => 'Solicitud generada desde el cotizador.',
            ]);

            $fecha = now()->subDays($diasAtras);
            $solicitud->forceFill(['created_at' => $fecha, 'updated_at' => $fecha])->save();
        }
    }

    private function resumen(): void
    {
        $this->command?->newLine();
        $this->command?->info('Datos de demostración listos.');
        $this->command?->line('  Clientes: '.Cliente::count().' ('.Cliente::where('es_temporal', true)->count().' prospectos)');
        $this->command?->line('  Sedes: '.ClienteSucursal::count());
        $this->command?->line('  Productos: '.Producto::count().'  ·  Listas de precios: '.ListaPrecio::count());
        $this->command?->line('  Existencias: '.StockProducto::count().' filas  ·  Movimientos: '.MovimientoStock::count());
        $this->command?->line('  Órdenes de servicio: '.OrdenServicio::count().'  ·  Solicitudes: '.SolicitudCotizacion::count());
        $this->command?->newLine();
        $this->command?->line('  Entrar como: admin@portfolio.test / password');
        $this->command?->line('  También: vendedor@innpro.test y tecnico@innpro.test (misma clave)');
    }
}
