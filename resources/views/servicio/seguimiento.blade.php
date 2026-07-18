@php
    $flujo = ['recibida','en_diagnostico','en_proceso','espera_repuestos','finalizada','entregada'];
    $cancelada = $orden->estado === 'cancelada';
    $idxActual = array_search($orden->estado, $flujo);
    if ($idxActual === false) $idxActual = -1;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Seguimiento {{ $orden->numero }} · Innpro</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}"/>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=chakra-petch:500,600,700|sora:400,500,600,700&display=swap" rel="stylesheet">
    <style>
        :root{
            --in-indigo:#241D5E; --in-blue:#3488BD; --in-blue-600:#12669B;
            --in-navy:#020B3C; --in-teal:#5DCEBA; --in-teal-600:#2AA995;
            --disp:'Chakra Petch',sans-serif; --body:'Sora',system-ui,sans-serif;
        }
        *{box-sizing:border-box;}
        body{margin:0;font-family:var(--body);color:#22252f;background:#eaeef7;
            background-image:radial-gradient(1000px 500px at 100% -5%,rgba(52,136,189,.12),transparent 55%),
                radial-gradient(900px 500px at 0% -8%,rgba(36,29,94,.12),transparent 60%);
            background-attachment:fixed;min-height:100vh;}
        .wrap{max-width:820px;margin:0 auto;padding:2rem 1.2rem 3rem;}

        /* Encabezado */
        .top{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.6rem;flex-wrap:wrap;}
        .brand-plate{background:#fff;border-radius:14px;padding:.6rem .9rem;box-shadow:0 12px 30px rgba(2,11,60,.14);position:relative;}
        .brand-plate::before,.brand-plate::after{content:"";position:absolute;width:12px;height:12px;border:2px solid var(--in-teal);}
        .brand-plate::before{top:-5px;left:-5px;border-right:0;border-bottom:0;border-radius:3px 0 0 0;}
        .brand-plate::after{bottom:-5px;right:-5px;border-left:0;border-top:0;border-radius:0 0 3px 0;}
        .brand-plate img{display:block;height:44px;}
        .top .eyebrow{font-family:var(--disp);font-size:.68rem;letter-spacing:.22em;text-transform:uppercase;color:var(--in-blue-600);}
        .top .eyebrow b{color:var(--in-indigo);}

        /* Tarjeta principal */
        .card{background:#fff;border:1px solid rgba(36,29,94,.07);border-radius:18px;box-shadow:0 14px 40px rgba(2,11,60,.08);padding:1.6rem 1.5rem;margin-bottom:1.3rem;}
        .hero{background:radial-gradient(120% 130% at 12% 10%,#12669B,#241D5E 55%,#020B3C);color:#fff;border:0;position:relative;overflow:hidden;}
        .hero::before{content:"";position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.05) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.05) 1px,transparent 1px);background-size:36px 36px;mask-image:radial-gradient(circle at 80% 10%,#000,transparent 70%);-webkit-mask-image:radial-gradient(circle at 80% 10%,#000,transparent 70%);}
        .hero .rel{position:relative;z-index:1;}
        .hero .num{font-family:var(--disp);font-size:1.6rem;font-weight:700;letter-spacing:.02em;}
        .hero .titulo{font-size:1.05rem;margin-top:.15rem;color:rgba(255,255,255,.86);}
        .hero .meta{margin-top:.9rem;font-size:.86rem;color:rgba(255,255,255,.72);line-height:1.7;}
        .hero .meta b{color:#fff;font-weight:600;}
        .pill{display:inline-block;font-family:var(--disp);font-size:.74rem;font-weight:600;letter-spacing:.05em;
            padding:.32rem .8rem;border-radius:20px;background:var(--in-teal-600);color:#fff;text-transform:uppercase;}
        .pill.cancel{background:#E4572E;}

        /* Stepper */
        .stepper{display:flex;align-items:flex-start;justify-content:space-between;margin-top:.4rem;position:relative;}
        .stepper .step{flex:1;text-align:center;position:relative;}
        .stepper .step::before{content:"";position:absolute;top:11px;left:-50%;width:100%;height:3px;background:#e0e4ee;z-index:0;}
        .stepper .step:first-child::before{display:none;}
        .stepper .dot{width:24px;height:24px;border-radius:50%;background:#e0e4ee;margin:0 auto 8px;position:relative;z-index:1;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.7rem;font-weight:700;}
        .stepper .label{font-size:.68rem;color:#8a90a0;font-family:var(--disp);letter-spacing:.02em;line-height:1.2;}
        .stepper .step.done .dot{background:var(--in-teal-600);}
        .stepper .step.done::before{background:var(--in-teal-600);}
        .stepper .step.current .dot{background:var(--in-indigo);box-shadow:0 0 0 4px rgba(36,29,94,.16);}
        .stepper .step.current .label{color:var(--in-indigo);font-weight:600;}

        .sec-title{font-family:var(--disp);font-weight:600;color:var(--in-indigo);letter-spacing:.02em;margin:0 0 .9rem;font-size:1.02rem;}
        .equipo-row{display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid #eef0f6;font-size:.92rem;}
        .equipo-row:last-child{border-bottom:0;}
        .equipo-row .qty{color:#8a90a0;}

        /* Bitácora */
        .timeline{position:relative;padding-left:1.5rem;margin:0;list-style:none;}
        .timeline::before{content:"";position:absolute;left:7px;top:6px;bottom:6px;width:2px;background:rgba(36,29,94,.12);}
        .tl-item{position:relative;padding-bottom:1.2rem;}
        .tl-dot{position:absolute;left:-1.5rem;top:3px;width:15px;height:15px;border-radius:50%;background:var(--in-teal);box-shadow:0 0 0 3px rgba(93,206,186,.25);}
        .tl-body{background:#f7f9fd;border:1px solid rgba(36,29,94,.07);border-radius:12px;padding:.75rem .9rem;}
        .tl-meta{font-size:.78rem;color:#8a90a0;}
        .tl-meta b{color:var(--in-indigo);}
        .tl-desc{margin-top:.25rem;font-size:.93rem;}
        .tl-fotos{display:flex;gap:.35rem;flex-wrap:wrap;margin-top:.5rem;}
        .tl-fotos img{width:60px;height:60px;object-fit:cover;border-radius:8px;border:1px solid #e4e7f0;}

        .aviso{background:#f4f6fc;border:1px dashed rgba(36,29,94,.2);border-radius:12px;padding:1rem;color:#6b7185;text-align:center;font-size:.9rem;}
        .foot{text-align:center;font-family:var(--disp);font-size:.74rem;letter-spacing:.03em;color:#9aa0ad;margin-top:1.6rem;}
        .foot a{color:var(--in-blue-600);text-decoration:none;}
        @media(max-width:560px){.stepper .label{font-size:.58rem;}}
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <div class="brand-plate"><img src="{{ asset('images/logo.png') }}" alt="Innpro Ingeniería"></div>
            <div class="eyebrow">Seguimiento de servicio · <b>Innpro Ingeniería</b></div>
        </div>

        {{-- Hero --}}
        <div class="card hero">
            <div class="rel">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
                    <div>
                        <div class="num">{{ $orden->numero }}</div>
                        <div class="titulo">{{ $orden->titulo }}</div>
                    </div>
                    <span class="pill {{ $cancelada ? 'cancel' : '' }}">{{ $orden->estadoLabel() }}</span>
                </div>
                <div class="meta">
                    <b>Cliente:</b> {{ $orden->cliente?->nombre_empresa ?: $orden->cliente?->nombre_contacto }} &nbsp;·&nbsp;
                    <b>Técnico:</b> {{ $orden->tecnico?->name ?? 'Por asignar' }}<br>
                    <b>Ingreso:</b> {{ optional($orden->fecha_ingreso)->format('d/m/Y') }}
                    @if($orden->fecha_estimada) &nbsp;·&nbsp; <b>Entrega estimada:</b> {{ $orden->fecha_estimada->format('d/m/Y') }} @endif
                </div>
            </div>
        </div>

        {{-- Stepper de estado --}}
        @unless($cancelada)
        <div class="card">
            <div class="stepper">
                @foreach($flujo as $i => $est)
                    <div class="step {{ $i < $idxActual ? 'done' : '' }} {{ $i === $idxActual ? 'current' : '' }}">
                        <div class="dot">@if($i <= $idxActual)&#10003;@else{{ $i+1 }}@endif</div>
                        <div class="label">{{ $estados[$est]['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
        @endunless

        {{-- Equipos (solo lectura, sin precios) --}}
        @if($orden->equipos->isNotEmpty())
        <div class="card">
            <div class="sec-title">Equipos del servicio</div>
            @foreach($orden->equipos as $e)
                <div class="equipo-row"><span>{{ $e->descripcion }}</span><span class="qty">x{{ rtrim(rtrim(number_format($e->cantidad,2),'0'),'.') }}</span></div>
            @endforeach
        </div>
        @endif

        {{-- Bitácora (solo si el admin la habilitó) --}}
        <div class="card">
            <div class="sec-title">Avance del trabajo</div>
            @if(! $orden->bitacora_visible_cliente)
                <div class="aviso"><i>El detalle del avance aún no está habilitado para consulta. Si necesitas información, contáctanos.</i></div>
            @elseif($orden->bitacora->isEmpty())
                <div class="aviso">Todavía no hay registros de avance para esta orden.</div>
            @else
                <ul class="timeline">
                    @foreach($orden->bitacora as $b)
                        <li class="tl-item">
                            <div class="tl-dot"></div>
                            <div class="tl-body">
                                <div class="tl-meta"><b>{{ $b->tecnico?->name ?? 'Técnico Innpro' }}</b> · {{ $b->created_at->format('d/m/Y H:i') }}</div>
                                <div class="tl-desc">{{ $b->descripcion }}</div>
                                @if($b->fotos->isNotEmpty())
                                    <div class="tl-fotos">
                                        @foreach($b->fotos as $f)<a href="{{ $f->url }}" target="_blank"><img src="{{ $f->url }}"></a>@endforeach
                                    </div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="foot">
            Innpro Ingeniería SAS · Bogotá, Colombia · <a href="https://innproingenieria.com" target="_blank">innproingenieria.com</a>
        </div>
    </div>
</body>
</html>
