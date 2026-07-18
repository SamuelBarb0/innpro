<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Innpro') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}"/>
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=chakra-petch:400,500,600,700|sora:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --in-indigo: #241D5E;
                --in-blue: #3488BD;
                --in-blue-600: #12669B;
                --in-navy: #020B3C;
                --in-teal: #5DCEBA;
                --in-display: 'Chakra Petch', sans-serif;
                --in-body: 'Sora', system-ui, sans-serif;
            }
            * { box-sizing: border-box; }
            .innpro-auth { font-family: var(--in-body); }

            .auth-wrap { min-height: 100vh; display: flex; }

            /* ================= HERO (izquierda) ================= */
            .auth-hero {
                position: relative;
                flex: 1 1 48%;
                overflow: hidden;
                background: radial-gradient(130% 130% at 18% 8%, #12669B 0%, #241D5E 46%, #020B3C 100%);
                color: #fff;
                display: flex;
                flex-direction: column;
                justify-content: center;
                padding: 4rem;
            }
            /* rejilla técnica */
            .auth-hero::before {
                content: "";
                position: absolute; inset: 0;
                background-image:
                    linear-gradient(rgba(255,255,255,.055) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255,255,255,.055) 1px, transparent 1px);
                background-size: 46px 46px;
                mask-image: radial-gradient(circle at 32% 42%, #000 0%, transparent 78%);
                -webkit-mask-image: radial-gradient(circle at 32% 42%, #000 0%, transparent 78%);
            }
            /* línea de escaneo */
            .auth-hero::after {
                content: "";
                position: absolute; left: 0; right: 0; height: 180px;
                background: linear-gradient(180deg, transparent, rgba(93,206,186,.10), transparent);
                animation: scan 6s linear infinite;
                pointer-events: none;
            }
            @keyframes scan { 0% { top: -180px; } 100% { top: 100%; } }

            /* Retícula / lente (guiño al logo de cámara) */
            .auth-radar {
                position: absolute; right: -150px; top: 50%;
                width: 560px; height: 560px; transform: translateY(-50%);
                pointer-events: none;
            }
            .auth-radar .ring {
                position: absolute; inset: 0; margin: auto; border-radius: 50%;
                border: 1.5px solid rgba(93, 206, 186, .32);
            }
            .auth-radar .ring:nth-child(1) { width: 560px; height: 560px; animation: radarPulse 4.8s ease-out infinite; }
            .auth-radar .ring:nth-child(2) { width: 410px; height: 410px; animation: radarPulse 4.8s ease-out infinite .95s; }
            .auth-radar .ring:nth-child(3) { width: 260px; height: 260px; animation: radarPulse 4.8s ease-out infinite 1.9s; }
            @keyframes radarPulse {
                0% { transform: scale(.72); opacity: 0; }
                28% { opacity: 1; }
                100% { transform: scale(1.18); opacity: 0; }
            }
            /* núcleo de la lente + crosshair giratorio */
            .auth-lens {
                position: absolute; inset: 0; margin: auto;
                width: 140px; height: 140px; border-radius: 50%;
                border: 1.5px solid rgba(93, 206, 186, .9);
                box-shadow: 0 0 48px rgba(93, 206, 186, .45), inset 0 0 30px rgba(93, 206, 186, .25);
                background: radial-gradient(circle, rgba(93,206,186,.22), transparent 68%);
            }
            .auth-lens::before, .auth-lens::after {
                content: ""; position: absolute; background: rgba(93, 206, 186, .55);
            }
            .auth-lens::before { left: 50%; top: -22px; bottom: -22px; width: 1px; transform: translateX(-50%); }
            .auth-lens::after  { top: 50%; left: -22px; right: -22px; height: 1px; transform: translateY(-50%); }
            .auth-crosshair {
                position: absolute; inset: 0; margin: auto;
                width: 200px; height: 200px; border-radius: 50%;
                border-top: 2px solid rgba(93, 206, 186, .8);
                border-left: 2px solid transparent; border-right: 2px solid transparent; border-bottom: 2px solid transparent;
                animation: spin 7s linear infinite;
            }
            @keyframes spin { to { transform: rotate(360deg); } }

            /* nodos flotantes */
            .auth-node { position: absolute; width: 7px; height: 7px; border-radius: 50%; background: var(--in-teal); box-shadow: 0 0 14px var(--in-teal); opacity: .8; }
            .auth-node.n1 { left: 22%; top: 24%; animation: bob 5s ease-in-out infinite; }
            .auth-node.n2 { left: 38%; top: 70%; animation: bob 6.5s ease-in-out infinite .6s; }
            .auth-node.n3 { left: 60%; top: 30%; animation: bob 5.8s ease-in-out infinite 1.1s; }
            @keyframes bob { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }

            .auth-status {
                position: absolute; top: 2rem; left: 4rem; z-index: 3;
                font-family: var(--in-display); font-size: .68rem; letter-spacing: .22em;
                color: rgba(93, 206, 186, .9); display: flex; align-items: center; gap: .5rem;
            }
            .auth-status .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--in-teal); box-shadow: 0 0 10px var(--in-teal); animation: blink 1.6s ease-in-out infinite; }
            @keyframes blink { 0%,100% { opacity: 1; } 50% { opacity: .3; } }

            .auth-hero-content { position: relative; z-index: 2; max-width: 480px; animation: fadeUp .7s ease both; }
            .auth-eyebrow {
                display: inline-flex; align-items: center; gap: .55rem;
                font-family: var(--in-display); font-size: .72rem; letter-spacing: .26em;
                text-transform: uppercase; color: var(--in-teal); font-weight: 600; margin-bottom: 1.5rem;
            }
            .auth-eyebrow::before { content: ""; width: 30px; height: 2px; background: var(--in-teal); }
            .auth-hero h1 { font-family: var(--in-display); font-size: 2.9rem; font-weight: 700; line-height: 1.06; margin: 0 0 1.1rem; }
            .auth-hero h1 .accent { background: linear-gradient(90deg, #5DCEBA, #8fd8ff); -webkit-background-clip: text; background-clip: text; color: transparent; }
            .auth-hero p { color: rgba(255,255,255,.74); font-size: 1.02rem; line-height: 1.65; }

            .auth-hero-foot {
                position: absolute; bottom: 2.2rem; left: 4rem; right: 4rem; z-index: 2;
                font-family: var(--in-display); font-size: .74rem; letter-spacing: .04em;
                color: rgba(255,255,255,.55); display: flex; gap: 1.6rem; flex-wrap: wrap;
            }
            .auth-hero-foot span { display: inline-flex; align-items: center; gap: .45rem; }
            .auth-hero-foot span::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: var(--in-teal); }

            /* ================= FORMULARIO (derecha) ================= */
            .auth-form-side {
                flex: 1 1 52%;
                position: relative;
                background: #eef1f8;
                display: flex; flex-direction: column; align-items: center; justify-content: center;
                padding: 2.5rem 1.5rem;
            }
            .auth-form-side::before {
                content: ""; position: absolute; inset: 0;
                background-image:
                    linear-gradient(rgba(36,29,94,.04) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(36,29,94,.04) 1px, transparent 1px);
                background-size: 40px 40px;
                mask-image: radial-gradient(circle at 70% 30%, #000, transparent 80%);
                -webkit-mask-image: radial-gradient(circle at 70% 30%, #000, transparent 80%);
            }
            .auth-form-inner { position: relative; width: 100%; max-width: 430px; animation: fadeUp .7s ease both .12s; }

            .auth-logo-plate {
                position: relative; width: 220px; max-width: 66%; margin: 0 auto 1.9rem;
                background: #fff; border-radius: 16px; padding: .9rem 1.1rem;
                box-shadow: 0 16px 40px rgba(2, 11, 60, .16);
            }
            .auth-logo-plate::before, .auth-logo-plate::after {
                content: ""; position: absolute; width: 14px; height: 14px; border: 2px solid var(--in-teal);
            }
            .auth-logo-plate::before { top: -6px; left: -6px; border-right: 0; border-bottom: 0; border-radius: 3px 0 0 0; }
            .auth-logo-plate::after  { bottom: -6px; right: -6px; border-left: 0; border-top: 0; border-radius: 0 0 3px 0; }
            .auth-logo-plate img { display: block; width: 100%; animation: floaty 5s ease-in-out infinite; }
            @keyframes floaty { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }

            .auth-welcome { text-align: center; margin-bottom: 1.7rem; }
            .auth-welcome h2 { font-family: var(--in-display); font-size: 1.5rem; font-weight: 700; color: var(--in-indigo); margin: 0 0 .3rem; letter-spacing: .01em; }
            .auth-welcome p { color: #6b7280; font-size: .92rem; margin: 0; }

            .auth-card {
                background: #fff; border-radius: 18px; padding: 2rem 1.8rem;
                box-shadow: 0 22px 60px rgba(2, 11, 60, .12); border: 1px solid rgba(36, 29, 94, .06);
            }

            .innpro-auth label { font-family: var(--in-display); letter-spacing: .02em; color: #3a3f52; font-weight: 500; }
            .innpro-auth input[type=email]:focus,
            .innpro-auth input[type=password]:focus,
            .innpro-auth input[type=text]:focus {
                border-color: var(--in-blue) !important;
                box-shadow: 0 0 0 3px rgba(52, 136, 189, .18) !important;
            }
            .innpro-auth input[type=checkbox] { color: var(--in-indigo) !important; }
            .innpro-auth a { color: var(--in-blue-600); }

            .innpro-auth button[type=submit] {
                background: linear-gradient(120deg, #241D5E, #12669B 58%, #3488BD) !important;
                border: none !important; border-radius: 11px !important;
                font-family: var(--in-display) !important; letter-spacing: .08em;
                padding: .72rem 1.5rem !important;
                box-shadow: 0 10px 22px rgba(36, 29, 94, .30);
                position: relative; overflow: hidden;
                transition: transform .15s ease, box-shadow .15s ease, filter .15s ease !important;
            }
            .innpro-auth button[type=submit]:hover { transform: translateY(-2px); filter: brightness(1.09); box-shadow: 0 14px 28px rgba(36, 29, 94, .36); }

            @keyframes fadeUp { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: none; } }

            .auth-foot-note { text-align: center; margin-top: 1.5rem; font-family: var(--in-display); font-size: .74rem; letter-spacing: .04em; color: #9aa1ad; }

            @media (max-width: 900px) {
                .auth-hero { display: none; }
                .auth-form-side { flex: 1 1 100%; }
            }
            @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation: none !important; } }
        </style>
    </head>
    <body class="innpro-auth antialiased">
        <div class="auth-wrap">
            {{-- HERO --}}
            <aside class="auth-hero">
                <div class="auth-status"><span class="dot"></span> SISTEMA EN LÍNEA</div>

                <div class="auth-radar">
                    <span class="ring"></span><span class="ring"></span><span class="ring"></span>
                    <span class="auth-crosshair"></span>
                    <span class="auth-lens"></span>
                </div>
                <span class="auth-node n1"></span><span class="auth-node n2"></span><span class="auth-node n3"></span>

                <div class="auth-hero-content">
                    <span class="auth-eyebrow">Secure Technology</span>
                    <h1>Seguridad electrónica<br><span class="accent">e ingeniería</span></h1>
                    <p>Plataforma de gestión comercial y servicio técnico de Innpro Ingeniería. Cotizaciones, inventario y órdenes de servicio, monitoreados en un solo lugar.</p>
                </div>

                <div class="auth-hero-foot">
                    <span>Cotizaciones y catálogo</span>
                    <span>Inventario</span>
                    <span>Servicio técnico</span>
                </div>
            </aside>

            {{-- FORMULARIO --}}
            <main class="auth-form-side">
                <div class="auth-form-inner">
                    <div class="auth-logo-plate">
                        <img src="{{ asset('images/logo.png') }}" alt="Innpro Ingeniería">
                    </div>
                    <div class="auth-welcome">
                        <h2>Bienvenido</h2>
                        <p>Ingresa a tu panel de gestión</p>
                    </div>
                    <div class="auth-card">
                        {{ $slot }}
                    </div>
                    <div class="auth-foot-note">© {{ date('Y') }} INNPRO INGENIERÍA SAS · BOGOTÁ, COLOMBIA</div>
                </div>
            </main>
        </div>
    </body>
</html>
