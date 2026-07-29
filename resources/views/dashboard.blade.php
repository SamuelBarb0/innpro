<x-app-layout>
    <x-slot name="header">{{ __('Inicio') }}</x-slot>

    @push('styles')
    <style>
        .kpi-card {
            border: none;
            border-left: 4px solid var(--kpi-color, #007bff);
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.08); }
        .kpi-icon {
            width: 48px; height: 48px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: #fff;
            background: var(--kpi-color, #007bff);
        }
        .kpi-value { font-size: 2rem; font-weight: 700; line-height: 1; }
        .kpi-label { color: #6c757d; font-size: .85rem; text-transform: uppercase; letter-spacing: .5px; }
        .kpi-extra { font-size: .8rem; color: #6c757d; margin-top: 4px; }
        .sec-head {
            display: flex; align-items: center; gap: .6rem;
            margin: 1.6rem 0 .9rem;
            font-size: .82rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase;
            color: var(--in-indigo, #241D5E);
        }
        .sec-head::after { content: ""; flex: 1; height: 1px; background: linear-gradient(90deg, rgba(36,29,94,.25), transparent); }
    </style>
    @endpush

    <div class="container-fluid py-4">

        <div class="mb-3 d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <h4 class="mb-0">Dashboard</h4>
                <small class="text-muted">
                    @if($esTecnico)
                        Vista del técnico — solo tus órdenes
                    @elseif($esVendedor)
                        Vista del vendedor — solo tus clientes
                    @else
                        Vista global del sistema
                    @endif
                </small>
            </div>

            {{-- Filtro de periodo --}}
            <form method="GET" action="{{ route('dashboard') }}" class="d-flex flex-wrap align-items-end gap-2">
                <div>
                    <label class="form-label mb-0 small text-muted">Desde</label>
                    <input type="date" name="desde" class="form-control form-control-sm" value="{{ $desde->format('Y-m-d') }}">
                </div>
                <div>
                    <label class="form-label mb-0 small text-muted">Hasta</label>
                    <input type="date" name="hasta" class="form-control form-control-sm" value="{{ $hasta->format('Y-m-d') }}">
                </div>
                <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Aplicar</button>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
                <a href="{{ route('dashboard.exportar', ['desde' => $desde->format('Y-m-d'), 'hasta' => $hasta->format('Y-m-d')]) }}"
                   class="btn btn-outline-success btn-sm">
                    <i class="bi bi-file-earmark-excel me-1"></i>Exportar
                </a>
                @if($esAdmin)
                    {{-- Empresa ya no está en el menú: solo el administrador entra desde aquí
                         cuando necesita corregir el encabezado de las cotizaciones. --}}
                    <a href="{{ route('empresa.edit') }}" class="btn btn-outline-secondary btn-sm"
                       title="Datos que aparecen en el encabezado de las cotizaciones">
                        <i class="bi bi-building me-1"></i>Datos de la empresa
                    </a>
                @endif
            </form>
        </div>

        <div class="text-muted small mb-3">
            <i class="bi bi-calendar-range"></i>
            Periodo analizado: <strong>{{ $desde->translatedFormat('d M Y') }}</strong>
            a <strong>{{ $hasta->translatedFormat('d M Y') }}</strong>
        </div>

        @if($verServicio)
        {{-- ==================== SERVICIO TÉCNICO ==================== --}}
        <div class="sec-head"><i class="bi bi-tools"></i> Servicio técnico</div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card kpi-card h-100 shadow-sm" style="--kpi-color:#241D5E;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="kpi-icon"><i class="bi bi-clipboard-check"></i></span>
                        <div class="flex-grow-1">
                            <div class="kpi-label">Órdenes</div>
                            <div class="kpi-value">{{ number_format($ordenesTotal) }}</div>
                            <div class="kpi-extra">{{ number_format($ordenesCerradas) }} finalizadas, en garantía o facturadas</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card kpi-card h-100 shadow-sm" style="--kpi-color:#3488BD;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="kpi-icon"><i class="bi bi-hourglass-split"></i></span>
                        <div class="flex-grow-1">
                            <div class="kpi-label">Abiertas hoy</div>
                            <div class="kpi-value">{{ number_format($ordenesAbiertas) }}</div>
                            <div class="kpi-extra">
                                @if($ordenesVencidas > 0)
                                    <span class="text-danger"><i class="bi bi-exclamation-triangle"></i>
                                        <strong>{{ $ordenesVencidas }}</strong> pasadas de fecha estimada</span>
                                @else
                                    <span class="text-success">ninguna vencida</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card kpi-card h-100 shadow-sm" style="--kpi-color:#2AA995;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="kpi-icon"><i class="bi bi-speedometer2"></i></span>
                        <div class="flex-grow-1">
                            <div class="kpi-label">Tiempo de atención</div>
                            <div class="kpi-value">
                                {{ $diasPromedio !== null ? number_format($diasPromedio, 1) : '—' }}
                                <span class="fs-6 text-muted">días</span>
                            </div>
                            <div class="kpi-extra">promedio de ingreso a cierre</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card kpi-card h-100 shadow-sm" style="--kpi-color:#12669B;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="kpi-icon"><i class="bi bi-clock-history"></i></span>
                        <div class="flex-grow-1">
                            <div class="kpi-label">Horas registradas</div>
                            <div class="kpi-value">{{ number_format($horasRegistradas, 1) }}</div>
                            <div class="kpi-extra">según bitácoras del periodo</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-bar-chart-line"></i> Órdenes de servicio por mes</h6>
                    </div>
                    <div class="card-body">
                        @if(array_sum($osValores) > 0)
                            <canvas id="chartOrdenesMes" height="110"></canvas>
                        @else
                            <p class="text-muted mb-0">No hay órdenes en el periodo seleccionado.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-pie-chart"></i> Órdenes por estado</h6>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        @if(count($osEstadoValores) > 0)
                            <canvas id="chartOrdenesEstado" height="230"></canvas>
                        @else
                            <p class="text-muted mb-0">Sin datos en el periodo.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-2">
            @unless($esTecnico)
            <div class="col-12 col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-person-badge"></i> Productividad por técnico</h6>
                    </div>
                    <div class="card-body p-0">
                        @if($porTecnico->isEmpty())
                            <p class="text-muted p-3 mb-0">Sin órdenes en el periodo.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Técnico</th>
                                            <th class="text-end">Órdenes</th>
                                            <th class="text-end">Cerradas</th>
                                            <th class="text-end">Días prom.</th>
                                            <th class="text-end">Horas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($porTecnico as $t)
                                            <tr>
                                                <td>{{ $t->tecnico?->name ?? 'Sin asignar' }}</td>
                                                <td class="text-end">{{ $t->ordenes }}</td>
                                                <td class="text-end">{{ $t->cerradas }}</td>
                                                <td class="text-end">
                                                    {{ $t->dias_promedio !== null ? number_format($t->dias_promedio, 1) : '—' }}
                                                </td>
                                                <td class="text-end">{{ number_format((float) ($horasPorTecnico[$t->tecnico_id] ?? 0), 1) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endunless

            <div class="col-12 {{ $esTecnico ? '' : 'col-xl-6' }}">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-camera-video"></i> Equipos de seguridad más atendidos</h6>
                    </div>
                    <div class="card-body p-0">
                        @if($topEquipos->isEmpty())
                            <p class="text-muted p-3 mb-0">Aún no se han registrado equipos en las órdenes del periodo.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Equipo</th>
                                            <th class="text-end">Unidades</th>
                                            <th class="text-end">Órdenes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($topEquipos as $e)
                                            <tr>
                                                <td>{{ $e->descripcion }}</td>
                                                <td class="text-end">{{ rtrim(rtrim(number_format($e->unidades, 2), '0'), '.') }}</td>
                                                <td class="text-end">{{ $e->ordenes }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($verComercial)
        {{-- ==================== COMERCIAL ==================== --}}
        <div class="sec-head"><i class="bi bi-graph-up"></i> Comercial y catálogo</div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card kpi-card h-100 shadow-sm" style="--kpi-color:#241D5E;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="kpi-icon"><i class="bi bi-clipboard-data"></i></span>
                        <div class="flex-grow-1">
                            <div class="kpi-label">Solicitudes</div>
                            <div class="kpi-value">{{ number_format($solicitudesTotal) }}</div>
                            <div class="kpi-extra">
                                <i class="bi bi-graph-up-arrow text-success"></i>
                                <strong>{{ $solicitudesUltimos7 }}</strong> nuevas (últimos 7 días)
                                @if($solicitudesPendientes > 0)
                                    · <span class="text-warning"><strong>{{ $solicitudesPendientes }}</strong> pendientes</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card kpi-card h-100 shadow-sm" style="--kpi-color:#3488BD;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="kpi-icon"><i class="bi bi-people"></i></span>
                        <div class="flex-grow-1">
                            <div class="kpi-label">Clientes activos</div>
                            <div class="kpi-value">{{ number_format($clientesActivos) }}</div>
                            <div class="kpi-extra">{{ $esVendedor ? 'asignados a ti' : 'en el sistema' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card kpi-card h-100 shadow-sm" style="--kpi-color:#12669B;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="kpi-icon"><i class="bi bi-basket3"></i></span>
                        <div class="flex-grow-1">
                            <div class="kpi-label">Productos activos</div>
                            <div class="kpi-value">{{ number_format($productosActivos) }}</div>
                            <div class="kpi-extra">disponibles en catálogo</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card kpi-card h-100 shadow-sm" style="--kpi-color:#2AA995;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="kpi-icon"><i class="bi bi-box-seam"></i></span>
                        <div class="flex-grow-1">
                            <div class="kpi-label">Stock</div>
                            <div class="kpi-value">
                                <span class="text-success">{{ number_format($productosConStock) }}</span>
                                <span class="text-muted">/</span>
                                <span class="text-danger">{{ number_format($productosSinStock) }}</span>
                            </div>
                            <div class="kpi-extra">
                                <span class="text-success">con stock</span> ·
                                <span class="text-danger">sin stock</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-bar-chart-line"></i> Solicitudes por mes</h6>
                        <small class="text-muted">Total: {{ number_format($solicitudesTotal) }}</small>
                    </div>
                    <div class="card-body">
                        <canvas id="chartSolicitudesMes" height="110"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-pie-chart"></i> Distribución de stock</h6>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        @if($productosActivos > 0)
                            <canvas id="chartStock" height="220"></canvas>
                        @else
                            <p class="text-muted mb-0">No hay productos activos.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-star"></i> Productos más solicitados</h6>
                    </div>
                    <div class="card-body p-0">
                        @if($topProductos->isEmpty())
                            <p class="text-muted p-3 mb-0">Sin solicitudes en el periodo.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Producto</th>
                                            <th>Referencia</th>
                                            <th class="text-end">Unidades</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($topProductos as $p)
                                            <tr>
                                                <td>{{ $p->nombre_producto }}</td>
                                                <td class="text-muted small">{{ $p->referencia_producto }}</td>
                                                <td class="text-end">{{ number_format($p->unidades) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-building"></i> Clientes con más solicitudes</h6>
                    </div>
                    <div class="card-body p-0">
                        @if($topClientes->isEmpty())
                            <p class="text-muted p-3 mb-0">Sin solicitudes en el periodo.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Cliente</th>
                                            <th class="text-end">Solicitudes</th>
                                            <th class="text-end">Monto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($topClientes as $c)
                                            <tr>
                                                <td>{{ $c->cliente?->nombre_empresa ?: $c->cliente?->nombre_contacto ?: 'Sin cliente' }}</td>
                                                <td class="text-end">{{ $c->solicitudes }}</td>
                                                <td class="text-end">$ {{ number_format((float) $c->monto, 0) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function() {
            const barra = (id, labels, data, etiqueta) => {
                const ctx = document.getElementById(id);
                if (!ctx) return;
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: etiqueta,
                            data: data,
                            backgroundColor: 'rgba(52, 136, 189, .70)',
                            borderColor: 'rgba(36, 29, 94, 1)',
                            borderWidth: 1,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                    }
                });
            };

            const dona = (id, labels, data, colores) => {
                const ctx = document.getElementById(id);
                if (!ctx) return;
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{ data: data, backgroundColor: colores, borderWidth: 0 }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } },
                        cutout: '60%'
                    }
                });
            };

            @if($verServicio)
                barra('chartOrdenesMes', @json($osLabels), @json($osValores), 'Órdenes');
                dona('chartOrdenesEstado', @json($osEstadoLabels), @json($osEstadoValores), @json($osEstadoColores));
            @endif

            @if($verComercial)
                barra('chartSolicitudesMes', @json($chartLabels), @json($chartValores), 'Solicitudes');
                dona('chartStock', ['Con stock', 'Sin stock'],
                     [{{ $productosConStock }}, {{ $productosSinStock }}], ['#2AA995', '#E4572E']);
            @endif
        })();
    </script>
    @endpush
</x-app-layout>
