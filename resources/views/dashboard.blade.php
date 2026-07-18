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
    </style>
    @endpush

    <div class="container-fluid py-4">

        <div class="mb-3 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">Dashboard</h4>
                <small class="text-muted">
                    {{ $esVendedor ? 'Vista del vendedor — solo tus clientes' : 'Vista global del sistema' }}
                </small>
            </div>
            <small class="text-muted">{{ now()->translatedFormat('l, d \\d\\e F \\d\\e Y') }}</small>
        </div>

        {{-- ========== TARJETAS KPI ========== --}}
        <div class="row g-3 mb-4">
            {{-- Solicitudes --}}
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

            {{-- Clientes activos --}}
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card kpi-card h-100 shadow-sm" style="--kpi-color:#3488BD;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="kpi-icon"><i class="bi bi-people"></i></span>
                        <div class="flex-grow-1">
                            <div class="kpi-label">Clientes activos</div>
                            <div class="kpi-value">{{ number_format($clientesActivos) }}</div>
                            <div class="kpi-extra">
                                {{ $esVendedor ? 'asignados a ti' : 'en el sistema' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Productos activos --}}
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

            {{-- Stock disponible / sin stock --}}
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

        {{-- ========== GRÁFICO Solicitudes por mes ========== --}}
        <div class="row g-3">
            <div class="col-12 col-xl-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-bar-chart-line"></i> Solicitudes por mes (últimos 12 meses)</h6>
                        <small class="text-muted">Total: {{ number_format($solicitudesTotal) }}</small>
                    </div>
                    <div class="card-body">
                        <canvas id="chartSolicitudesMes" height="110"></canvas>
                    </div>
                </div>
            </div>

            {{-- Distribución stock (donut) --}}
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
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function() {
            // ----- Solicitudes por mes -----
            const ctxMes = document.getElementById('chartSolicitudesMes');
            if (ctxMes) {
                new Chart(ctxMes, {
                    type: 'bar',
                    data: {
                        labels: @json($chartLabels),
                        datasets: [{
                            label: 'Solicitudes',
                            data: @json($chartValores),
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
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 } }
                        }
                    }
                });
            }

            // ----- Distribución stock (donut) -----
            const ctxStock = document.getElementById('chartStock');
            if (ctxStock) {
                new Chart(ctxStock, {
                    type: 'doughnut',
                    data: {
                        labels: ['Con stock', 'Sin stock'],
                        datasets: [{
                            data: [{{ $productosConStock }}, {{ $productosSinStock }}],
                            backgroundColor: ['#2AA995', '#E4572E'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } },
                        cutout: '60%'
                    }
                });
            }
        })();
    </script>
    @endpush
</x-app-layout>
