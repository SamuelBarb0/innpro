@php($empresa = \App\Models\Parametros::empresa())
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $solicitud->nombre_archivo_pdf }}</title>
    <style>
        @page { margin: 135px 25px 60px 25px; }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        /* ===== Header con logo ===== */
        .header {
            position: fixed;
            top: -120px;
            left: 0;
            right: 0;
            height: 110px;
            border-bottom: 2px solid #007bff;
            padding: 0;
        }
        .header table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; padding: 0; }
        .header .logo-cell { width: 130px; vertical-align: middle; }
        .header .logo-cell img { height: 55px; }
        .header .empresa-cell { padding-left: 8px; }
        .header .empresa-cell .razon { font-size: 13px; font-weight: bold; color: #007bff; }
        .header .empresa-cell .meta { font-size: 9px; color: #555; line-height: 1.35; margin-top: 2px; }
        .header .title-cell { text-align: right; padding-right: 5px; vertical-align: top; }
        .header h2 { margin: 0; color: #007bff; font-size: 16px; }
        .header .codigo { font-size: 11px; color: #555; margin-top: 2px; }

        /* ===== Footer ===== */
        .footer {
            position: fixed;
            bottom: -45px;
            left: 0; right: 0;
            height: 35px;
            text-align: center;
            font-size: 9px;
            color: #888;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
        .page-number:after { content: counter(page); }

        /* ===== Watermark ===== */
        .watermark {
            position: fixed;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 72px;
            color: rgba(0, 123, 255, 0.07);
            font-weight: bold;
            z-index: -1;
        }

        /* ===== Cliente compacto: una sola tabla 2-col ===== */
        .section-title {
            font-size: 12px;
            color: #007bff;
            font-weight: bold;
            margin: 8px 0 4px;
            text-transform: uppercase;
            letter-spacing: .5px;
            border-bottom: 1px solid #007bff;
            padding-bottom: 2px;
        }

        .info-compact {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .info-compact td {
            padding: 2px 4px;
            font-size: 10.5px;
            border: none;
            vertical-align: top;
        }
        .info-compact .lbl { color: #666; width: 80px; }
        .info-compact .val { font-weight: bold; }

        /* ===== Tabla items ===== */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
        }
        table.items th {
            background-color: #007bff;
            color: #fff;
            border: 1px solid #007bff;
            padding: 5px 3px;
            text-align: left;
            font-size: 8.5px;
            font-weight: bold;
        }
        table.items td {
            border: 1px solid #dee2e6;
            padding: 4px 3px;
            font-size: 9px;
        }
        table.items tr:nth-child(even) td { background-color: #f9f9f9; }

        .product-image {
            width: 40px; height: 40px;
            object-fit: cover;
            border-radius: 3px;
        }

        .text-center { text-align: center; }
        .text-right  { text-align: right; }

        .total-row td {
            background-color: #e9f3ff;
            font-weight: bold;
            font-size: 12px;
            border-top: 2px solid #007bff;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-success { background-color: #28a745; color: #fff; }
        .badge-warning { background-color: #ffc107; color: #212529; }

        .notes-box {
            background-color: #f8f9fa;
            border-left: 3px solid #007bff;
            padding: 6px 10px;
            margin: 8px 0;
            font-size: 10.5px;
        }
        .notes-box .nb-title {
            font-weight: bold;
            margin-bottom: 3px;
        }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td class="logo-cell">
                    <img src="{{ public_path('images/logo.png') }}" alt="{{ $empresa['razon_social'] }}">
                </td>
                <td class="empresa-cell">
                    <div class="razon">{{ $empresa['razon_social'] }}</div>
                    <div class="meta">
                        @if($empresa['ruc'])RUC/CC: {{ $empresa['ruc'] }}<br>@endif
                        @if($empresa['direccion']){{ $empresa['direccion'] }}<br>@endif
                        @if($empresa['telefonos'])Tel: {{ $empresa['telefonos'] }}@if($empresa['email']) &nbsp;·&nbsp; @endif @endif
                        @if($empresa['email']){{ $empresa['email'] }}@endif
                        @if($empresa['sitio_web'])<br>{{ $empresa['sitio_web'] }}@endif
                    </div>
                </td>
                <td class="title-cell">
                    <h2>COTIZACIÓN</h2>
                    <div class="codigo">{{ $solicitud->codigo_corto }} &nbsp;·&nbsp; {{ $solicitud->created_at->format('d/m/Y H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Página <span class="page-number"></span> &nbsp;·&nbsp; Generado el {{ now()->format('d/m/Y H:i') }} &nbsp;·&nbsp; {{ $solicitud->nombre_archivo_pdf }}
    </div>

    <div class="watermark">
        {{ $solicitud->estado === 'aplicada' ? 'CONFIRMADA' : 'EN ESTUDIO' }}
    </div>

    {{-- Cliente + Solicitud en una sola fila de dos columnas --}}
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:50%; vertical-align:top; padding-right:8px;">
                <div class="section-title">Cliente</div>
                <table class="info-compact">
                    <tr><td class="lbl">Nombre:</td><td class="val">{{ $solicitud->cliente?->nombre_contacto ?? 'Cliente eliminado' }}</td></tr>
                    <tr><td class="lbl">Empresa:</td><td class="val">{{ $solicitud->cliente?->nombre_empresa ?: '—' }}</td></tr>
                    <tr><td class="lbl">NIT/CC:</td><td>{{ $solicitud->cliente?->numero_identificacion ?? '—' }}</td></tr>
                    <tr><td class="lbl">Email:</td><td>{{ $solicitud->cliente?->email ?? '—' }}</td></tr>
                    <tr><td class="lbl">Teléfono:</td><td>{{ $solicitud->cliente?->telefono ?: '—' }}</td></tr>
                    <tr><td class="lbl">Ubicación:</td><td>{{ trim(($solicitud->cliente?->ciudad ?? '') . ', ' . ($solicitud->cliente?->pais ?? ''), ', ') ?: '—' }}</td></tr>
                </table>
            </td>
            <td style="width:50%; vertical-align:top; padding-left:8px;">
                <div class="section-title">Solicitud</div>
                <table class="info-compact">
                    <tr><td class="lbl">Código:</td><td class="val">{{ $solicitud->codigo_corto }}</td></tr>
                    <tr><td class="lbl">Estado:</td>
                        <td>
                            <span class="badge {{ $solicitud->estado === 'aplicada' ? 'badge-success' : 'badge-warning' }}">
                                {{ $solicitud->estado === 'aplicada' ? 'CONFIRMADA' : 'EN ESTUDIO' }}
                            </span>
                        </td>
                    </tr>
                    <tr><td class="lbl">Vendedor:</td><td>{{ $solicitud->cliente?->vendedor?->name ?? '—' }}</td></tr>
                    @if($solicitud->estado === 'aplicada')
                    <tr><td class="lbl">Confirmada:</td><td>{{ $solicitud->aplicada_en->format('d/m/Y H:i') }}</td></tr>
                    <tr><td class="lbl">Procesó:</td><td>{{ $solicitud->aplicadaPor?->name ?? '—' }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">Productos solicitados ({{ $solicitud->total_items }} unidades)</div>
    <table class="items">
        <thead>
            <tr>
                <th width="38">Img.</th>
                <th width="52">Ref.</th>
                <th>Producto</th>
                <th width="32" class="text-center">Cant.</th>
                <th width="60" class="text-right">Precio Unit.</th>
                <th width="32" class="text-center">Und</th>
                <th width="68" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($solicitud->items as $item)
            <tr>
                <td class="text-center">
                    @if($item->producto && $item->producto->imagenPrincipal)
                        <img src="{{ public_path($item->producto->imagenPrincipal->ruta_imagen) }}"
                             class="product-image"
                             alt="{{ $item->nombre_producto }}">
                    @else
                        <div style="width:40px;height:40px;background:#f8f9fa;display:inline-block;text-align:center;line-height:40px;color:#bbb;font-size:8px;">
                            s/img
                        </div>
                    @endif
                </td>
                <td>{{ $item->referencia_producto }}</td>
                <td>
                    {{ $item->nombre_producto }}
                    @if($item->info_variante)
                        <br><small style="color:#666;">{{ $item->info_variante }}</small>
                    @endif
                </td>
                <td class="text-center">{{ $item->cantidad }}</td>
                <td class="text-right">${{ number_format($item->precio_unitario, 2) }}</td>
                <td class="text-center">{{ $item->producto?->unidad_venta ?: '' }}</td>
                <td class="text-right">${{ number_format($item->precio_total, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" style="text-align:left;">Items: {{ $solicitud->items->count() }}</td>
                <td class="text-center">{{ $solicitud->total_items }}</td>
                <td></td>
                <td class="text-right">${{ number_format($solicitud->monto_total, 2) }}</td>
            </tr>
        </tbody>
    </table>

    @if($solicitud->notas_cliente)
    <div class="notes-box">
        <div class="nb-title">Notas del cliente:</div>
        {{ $solicitud->notas_cliente }}
    </div>
    @endif

    <?php
        // Solo deben imprimirse los comentarios comerciales, nunca los movimientos
        // de stock ni los equipos contemplados. Para registros antiguos que todavía
        // traen ese bloque anexado, lo cortamos a partir de su encabezado.
        $obsComercial = trim(preg_split('/\n*\s*Movimientos de stock/i', (string) $solicitud->observaciones_admin)[0] ?? '');
    ?>
    @if($obsComercial !== '')
    <div class="notes-box">
        <div class="nb-title">Observaciones del vendedor:</div>
        {{ $obsComercial }}
    </div>
    @endif
</body>
</html>
