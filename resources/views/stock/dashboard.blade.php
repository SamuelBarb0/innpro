<x-app-layout>
    <x-slot name="header">Dashboard de Inventario</x-slot>

    @push('styles')
    <style>
        .kpi-card { border: none; border-left: 4px solid var(--kpi-color, #3488BD); transition: transform .15s ease, box-shadow .15s ease; }
        .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(2,11,60,.10); }
        .kpi-icon { width: 48px; height: 48px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 1.4rem; color: #fff; background: var(--kpi-color, #3488BD); }
        .kpi-value { font-size: 2rem; font-weight: 700; line-height: 1; }
        .kpi-label { color: #6c757d; font-size: .85rem; text-transform: uppercase; letter-spacing: .5px; }
    </style>
    @endpush

    <div class="container-fluid py-4">

        <div class="mb-3 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">Dashboard de Inventario</h4>
                <small class="text-muted">Resumen de stock y movimientos</small>
            </div>
            <a href="{{ route('stock.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Volver a Gestión de Stock
            </a>
        </div>

        {{-- KPIs --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card kpi-card h-100 shadow-sm" style="--kpi-color:#241D5E;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="kpi-icon"><i class="bi bi-box-seam"></i></span>
                        <div>
                            <div class="kpi-label">Productos con control</div>
                            <div class="kpi-value">{{ number_format($totalProductos) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card kpi-card h-100 shadow-sm" style="--kpi-color:#2AA995;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="kpi-icon"><i class="bi bi-check2-circle"></i></span>
                        <div>
                            <div class="kpi-label">Con stock</div>
                            <div class="kpi-value">{{ number_format($productosConStock) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card kpi-card h-100 shadow-sm" style="--kpi-color:#E4572E;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="kpi-icon"><i class="bi bi-x-circle"></i></span>
                        <div>
                            <div class="kpi-label">Sin stock</div>
                            <div class="kpi-value">{{ number_format($productosSinStock) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card kpi-card h-100 shadow-sm" style="--kpi-color:#E0A400;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="kpi-icon"><i class="bi bi-exclamation-triangle"></i></span>
                        <div>
                            <div class="kpi-label">Stock bajo</div>
                            <div class="kpi-value">{{ number_format($productosStockBajo) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Movimientos del mes --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="kpi-label">Entradas del mes</div>
                            <div class="kpi-value text-success">{{ number_format($entradasMes) }}</div>
                        </div>
                        <i class="bi bi-arrow-down-circle text-success" style="font-size:2rem;"></i>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="kpi-label">Salidas del mes</div>
                            <div class="kpi-value text-danger">{{ number_format($salidasMes) }}</div>
                        </div>
                        <i class="bi bi-arrow-up-circle text-danger" style="font-size:2rem;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            {{-- Top rotación --}}
            <div class="col-12 col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><h6 class="mb-0"><i class="bi bi-graph-up-arrow"></i> Mayor rotación (último mes)</h6></div>
                    <div class="card-body">
                        @if($productosTopRotacion->isEmpty())
                            <p class="text-muted small mb-0">Sin movimientos de salida en el último mes.</p>
                        @else
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr class="text-muted small"><th>Producto</th><th class="text-end">Unidades movidas</th></tr></thead>
                                <tbody>
                                    @foreach($productosTopRotacion as $row)
                                        <tr>
                                            <td>{{ optional(\App\Models\Producto::find($row->producto_id))->nombre ?? 'Producto #'.$row->producto_id }}</td>
                                            <td class="text-end fw-semibold">{{ number_format($row->total_movimiento) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Productos críticos --}}
            <div class="col-12 col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><h6 class="mb-0"><i class="bi bi-exclamation-triangle text-warning"></i> Productos críticos (stock bajo)</h6></div>
                    <div class="card-body">
                        @if($productosCriticos->isEmpty())
                            <p class="text-muted small mb-0">Ningún producto por debajo de su mínimo. 👍</p>
                        @else
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr class="text-muted small"><th>Producto</th><th>Variante</th><th class="text-end">Disponible</th></tr></thead>
                                <tbody>
                                    @foreach($productosCriticos as $s)
                                        <tr>
                                            <td>{{ $s->producto?->nombre ?? '—' }}</td>
                                            <td class="text-muted small">{{ $s->variante?->nombre_variante ?? '—' }}</td>
                                            <td class="text-end fw-semibold text-danger">{{ number_format($s->cantidad_disponible) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
