@php($empresa = \App\Models\Parametros::empresa())
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Orden {{ $orden->numero }}</title>
    <style>
        @page { margin: 135px 28px 70px 28px; }

        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #2b2f3a; margin: 0; padding: 0; }

        /* ===== Header ===== */
        .header {
            position: fixed; top: -120px; left: 0; right: 0; height: 112px;
            border-bottom: 3px solid #241D5E; padding: 0;
        }
        .header table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: middle; padding: 0; }
        .header .logo-cell { width: 150px; }
        .header .logo-cell img { height: 58px; }
        .header .empresa-cell { padding-left: 10px; }
        .header .empresa-cell .razon { font-size: 13px; font-weight: bold; color: #241D5E; }
        .header .empresa-cell .meta { font-size: 9px; color: #555; line-height: 1.4; margin-top: 2px; }
        .header .title-cell { text-align: right; vertical-align: top; padding-top: 4px; }
        .header h2 { margin: 0; color: #241D5E; font-size: 17px; letter-spacing: .5px; }
        .header .codigo { font-size: 12px; color: #12669B; font-weight: bold; margin-top: 3px; }
        .header .estado-tag {
            display: inline-block; margin-top: 4px; font-size: 9px; font-weight: bold;
            color: #fff; background: #2AA995; padding: 2px 8px; border-radius: 8px; text-transform: uppercase;
        }

        /* ===== Footer ===== */
        .footer {
            position: fixed; bottom: -50px; left: 0; right: 0; height: 40px;
            text-align: center; font-size: 8.5px; color: #8a8f9c;
            border-top: 1px solid #e6e8ef; padding-top: 6px;
        }
        .page-number:after { content: counter(page); }

        /* ===== Bloques ===== */
        .section-title {
            font-size: 11px; font-weight: bold; color: #241D5E; text-transform: uppercase;
            letter-spacing: .6px; border-left: 3px solid #2AA995; padding-left: 7px;
            margin: 16px 0 7px;
        }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .info-table td { vertical-align: top; padding: 0 6px; width: 50%; }
        .box { border: 1px solid #e2e5ee; border-radius: 6px; padding: 8px 10px; background: #f8f9fc; }
        .box .lbl { color: #8a8f9c; font-size: 9px; text-transform: uppercase; }
        .box .row { margin-bottom: 3px; }
        .box .k { color: #6b7185; display: inline-block; width: 78px; }
        .box .v { color: #2b2f3a; font-weight: bold; }

        .desc-box { border: 1px solid #e2e5ee; border-radius: 6px; padding: 8px 10px; background: #fff; margin-bottom: 4px; }

        table.data { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.data th {
            background: #241D5E; color: #fff; font-size: 9.5px; text-transform: uppercase;
            padding: 6px 8px; text-align: left; letter-spacing: .3px;
        }
        table.data td { padding: 6px 8px; border-bottom: 1px solid #e9ebf2; font-size: 10.5px; }
        table.data tr:nth-child(even) td { background: #f6f7fb; }
        .num { text-align: right; }

        /* Bitácora */
        .bit-entry { border-left: 2px solid #2AA995; padding: 3px 0 6px 10px; margin-bottom: 6px; }
        .bit-meta { font-size: 9px; color: #8a8f9c; }
        .bit-meta strong { color: #241D5E; }
        .bit-desc { font-size: 10.5px; margin-top: 2px; }

        /* Firmas */
        .firmas { width: 100%; margin-top: 10px; border-collapse: collapse; page-break-inside: avoid; }
        .firmas td { width: 50%; padding: 0 22px; text-align: center; vertical-align: bottom; }
        .firma-img { height: 62px; text-align: center; }
        .firma-img img { max-height: 60px; max-width: 100%; }
        .firma-line { border-top: 1px solid #6b7185; padding-top: 5px; font-size: 10px; color: #4b5061; }
        .firma-line .rol { color: #8a8f9c; font-size: 9px; }

        .muted { color: #9aa0ad; font-style: italic; }
    </style>
</head>
<body>
    {{-- ===== Encabezado fijo ===== --}}
    <div class="header">
        <table>
            <tr>
                <td class="logo-cell"><img src="{{ public_path('images/logo.png') }}" alt="{{ $empresa['razon_social'] }}"></td>
                <td class="empresa-cell">
                    <div class="razon">{{ $empresa['razon_social'] }}</div>
                    <div class="meta">
                        @if($empresa['ruc'])NIT/CC: {{ $empresa['ruc'] }}<br>@endif
                        @if($empresa['direccion']){{ $empresa['direccion'] }}<br>@endif
                        @if($empresa['telefonos'])Tel: {{ $empresa['telefonos'] }}@endif
                        @if($empresa['email']) &nbsp;·&nbsp; {{ $empresa['email'] }}@endif
                        @if($empresa['sitio_web'])<br>{{ $empresa['sitio_web'] }}@endif
                    </div>
                </td>
                <td class="title-cell">
                    <h2>ORDEN DE SERVICIO</h2>
                    <div class="codigo">{{ $orden->numero }}</div>
                    <span class="estado-tag">{{ $orden->estadoLabel() }}</span>
                </td>
            </tr>
        </table>
    </div>

    {{-- ===== Pie fijo ===== --}}
    <div class="footer">
        {{ $empresa['razon_social'] }} @if($empresa['telefonos']) · {{ $empresa['telefonos'] }} @endif · Documento generado el {{ now()->format('d/m/Y H:i') }} · Página <span class="page-number"></span>
    </div>

    {{-- ===== Cuerpo ===== --}}
    <table class="info-table">
        <tr>
            <td>
                <div class="box">
                    <div class="lbl">Cliente</div>
                    <div class="row"><span class="v">{{ $orden->cliente?->nombre_empresa ?: $orden->cliente?->nombre_contacto }}</span></div>
                    <div class="row"><span class="k">Contacto:</span> {{ $orden->cliente?->nombre_contacto }}</div>
                    <div class="row"><span class="k">NIT/CC:</span> {{ $orden->cliente?->numero_identificacion }}</div>
                    <div class="row"><span class="k">Teléfono:</span> {{ $orden->cliente?->telefono ?: '—' }}</div>
                    <div class="row"><span class="k">Ciudad:</span> {{ $orden->cliente?->ciudad }}</div>
                </div>
            </td>
            <td>
                <div class="box">
                    <div class="lbl">Orden</div>
                    <div class="row"><span class="k">Ingreso:</span> <span class="v">{{ optional($orden->fecha_ingreso)->format('d/m/Y') }}</span></div>
                    <div class="row"><span class="k">Estimada:</span> {{ optional($orden->fecha_estimada)->format('d/m/Y') ?: '—' }}</div>
                    <div class="row"><span class="k">Técnico:</span> {{ $orden->tecnico?->name ?? 'Sin asignar' }}</div>
                    <div class="row"><span class="k">Prioridad:</span> {{ $orden->prioridadLabel() }}</div>
                    <div class="row"><span class="k">Estado:</span> {{ $orden->estadoLabel() }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Trabajo solicitado</div>
    <div class="desc-box">
        <strong>{{ $orden->titulo }}</strong>
        @if($orden->descripcion_problema)<div style="margin-top:4px;">{{ $orden->descripcion_problema }}</div>@endif
    </div>

    @if($orden->diagnostico)
        <div class="section-title">Diagnóstico técnico</div>
        <div class="desc-box">{{ $orden->diagnostico }}</div>
    @endif

    {{-- Equipos --}}
    <div class="section-title">Equipos de seguridad</div>
    @if($orden->equipos->isEmpty())
        <div class="desc-box muted">Sin equipos registrados.</div>
    @else
        <table class="data">
            <thead><tr><th>Descripción</th><th class="num" style="width:70px;">Cant.</th></tr></thead>
            <tbody>
                @foreach($orden->equipos as $it)
                    <tr>
                        <td>{{ $it->descripcion }}@if($it->notas)<br><span class="muted" style="font-size:9px">{{ $it->notas }}</span>@endif</td>
                        <td class="num">{{ rtrim(rtrim(number_format($it->cantidad,2),'0'),'.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Repuestos --}}
    @if($orden->repuestos->isNotEmpty())
        <div class="section-title">Repuestos utilizados</div>
        <table class="data">
            <thead><tr><th>Descripción</th><th class="num" style="width:70px;">Cant.</th></tr></thead>
            <tbody>
                @foreach($orden->repuestos as $it)
                    <tr>
                        <td>{{ $it->descripcion }}@if($it->notas)<br><span class="muted" style="font-size:9px">{{ $it->notas }}</span>@endif</td>
                        <td class="num">{{ rtrim(rtrim(number_format($it->cantidad,2),'0'),'.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- El costo del servicio NO se incluye en el formato técnico: se define en el proceso de cotización. --}}

    {{-- Bitácora (B4: incluida en el PDF) --}}
    <div class="section-title" style="margin-top:20px;">Bitácora del trabajo</div>
    @if($orden->bitacora->isEmpty())
        <div class="desc-box muted">Sin entradas de bitácora.</div>
    @else
        @foreach($orden->bitacora->sortBy('created_at') as $b)
            <div class="bit-entry">
                <div class="bit-meta"><strong>{{ $b->tecnico?->name ?? 'Técnico' }}</strong> · {{ $b->created_at->format('d/m/Y H:i') }}@if($b->horas_trabajadas) · {{ number_format($b->horas_trabajadas,1) }} h @endif</div>
                <div class="bit-desc">{{ $b->descripcion }}</div>
                @if($b->observaciones)<div class="bit-meta" style="margin-top:2px;">Obs: {{ $b->observaciones }}</div>@endif
            </div>
        @endforeach
        <div style="font-size:9.5px; color:#6b7185; margin-top:4px;">Total de horas registradas: <strong>{{ number_format($orden->horas_totales,1) }} h</strong></div>
    @endif

    {{-- Firmas (capturadas con el colector de firmas; si no hay, queda el espacio para firmar a mano) --}}
    <div class="section-title" style="margin-top:22px;">Firmas</div>
    <table class="firmas">
        <tr>
            @foreach([
                ['tipo' => 'tecnico', 'rol' => 'Técnico responsable'],
                ['tipo' => 'cliente', 'rol' => 'Cliente — Recibido a conformidad'],
            ] as $f)
                @php($img = $orden->firmaDataUri($f['tipo']))
                <td>
                    <div class="firma-img">
                        @if($img)
                            <img src="{{ $img }}" alt="Firma">
                        @endif
                    </div>
                    <div class="firma-line">
                        {{ $orden->firmanteNombre($f['tipo']) ?: '&nbsp;' }}<br>
                        @if($orden->{"firma_{$f['tipo']}_cc"})
                            <span class="rol">C.C. {{ $orden->{"firma_{$f['tipo']}_cc"} }}</span><br>
                        @endif
                        <span class="rol">{{ $f['rol'] }}</span>
                        @if($orden->{"firma_{$f['tipo']}_at"})
                            <br><span class="rol">Firmado el {{ $orden->{"firma_{$f['tipo']}_at"}->format('d/m/Y H:i') }}</span>
                        @endif
                    </div>
                </td>
            @endforeach
        </tr>
    </table>
</body>
</html>
