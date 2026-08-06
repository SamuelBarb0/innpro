@php
    // Contador de solicitudes pendientes para badge en el menú
    $solicitudesPendientesCount = 0;
    try {
        $userActual = auth()->user();
        if ($userActual && $userActual->hasAnyRole(['admin', 'vendedor'])) {
            $q = \App\Models\SolicitudCotizacion::pendientes();
            if ($userActual->hasRole('vendedor') && !$userActual->hasRole('admin')) {
                $q->whereHas('cliente', function ($c) use ($userActual) {
                    $c->where('vendedor_id', $userActual->id);
                });
            }
            $solicitudesPendientesCount = $q->count();
        }
    } catch (\Throwable $e) {
        $solicitudesPendientesCount = 0;
    }
@endphp
<div class="d-flex flex-column h-100">
    {{-- Logo --}}
    <div class="d-flex justify-content-center align-items-center py-4 border-bottom">
        <a href="/" class="text-decoration-none brand-plate">
            <img src="{{ asset('images/logo.png') }}" class="logo-full" alt="Innpro Ingeniería">
        </a>
    </div>

    {{-- Etiqueta de sección --}}
    <div class="nav-section">Panel de control</div>

    {{-- Navegación --}}
    <nav class="d-flex flex-column flex-nowrap px-2 py-2 flex-grow-1 overflow-y-auto" style="min-height: 0;">
        <a href="/dashboard"
           class="nav-link mb-2 d-flex align-items-center gap-2 {{ request()->is('dashboard') ? 'active' : 'text-dark' }}">
            <i class="bi bi-house"></i>
            <span>Inicio</span>
        </a>

        @if (auth()->user()->getRoleNames()->first() == 'admin')
            <a href="/usuarios"
               class="nav-link mb-2 d-flex align-items-center gap-2 {{ request()->is('usuarios*') ? 'active' : 'text-dark' }}">
                <i class="bi bi-people"></i>
                <span>Usuarios</span>
            </a>
            <a href="/clientes"
               class="nav-link mb-2 d-flex align-items-center gap-2 {{ request()->is('clientes*') ? 'active' : 'text-dark' }}">
                <i class="bi bi-person-badge"></i>
                <span>Clientes</span>
            </a>
            {{-- Categorías se ocultó a petición de Innpro: no clasifican por categoría.
                 La ruta sigue viva por si hiciera falta retomarla. --}}
            <a href="/productos"
               class="nav-link mb-2 d-flex align-items-center gap-2 {{ request()->is('productos*') ? 'active' : 'text-dark' }}">
                <i class="bi bi-basket3"></i>
                <span>Productos</span>
            </a>
            <a href="{{ route('listas-precios') }}"
               class="nav-link mb-2 d-flex align-items-center gap-2 {{ request()->is('listas-precios*') ? 'active' : 'text-dark' }}">
                <i class="bi bi-tags"></i>
                <span>Listas de precios</span>
            </a>
            {{-- Sitio web público. Va en un desplegable porque son cuatro
                 pantallas que solo se tocan juntas, y meterlas sueltas al menú
                 lo alargaría para algo que se usa de vez en cuando. --}}
            <a class="nav-link mb-2 d-flex align-items-center gap-2 {{ request()->is('sitio*') ? 'active' : 'text-dark' }}"
               data-bs-toggle="collapse" href="#menuSitio" role="button"
               aria-expanded="{{ request()->is('sitio*') ? 'true' : 'false' }}">
                <i class="bi bi-globe2"></i>
                <span>Sitio web</span>
            </a>
            <div class="collapse {{ request()->is('sitio*') ? 'show' : '' }}" id="menuSitio">
                <a href="{{ route('sitio.admin.secciones') }}"
                   class="nav-link mb-1 ms-4 small d-flex align-items-center gap-2 {{ request()->is('sitio/secciones*') ? 'active' : 'text-dark' }}">
                    <i class="bi bi-layout-text-window"></i><span>Portada</span>
                </a>
                <a href="{{ route('sitio.admin.paginas') }}"
                   class="nav-link mb-1 ms-4 small d-flex align-items-center gap-2 {{ request()->is('sitio/paginas*') ? 'active' : 'text-dark' }}">
                    <i class="bi bi-file-earmark-text"></i><span>Páginas</span>
                </a>
                <a href="{{ route('sitio.admin.ajustes') }}"
                   class="nav-link mb-1 ms-4 small d-flex align-items-center gap-2 {{ request()->is('sitio/ajustes*') ? 'active' : 'text-dark' }}">
                    <i class="bi bi-sliders"></i><span>Ajustes y SEO</span>
                </a>
                <a href="{{ route('sitio.admin.redirecciones') }}"
                   class="nav-link mb-2 ms-4 small d-flex align-items-center gap-2 {{ request()->is('sitio/redirecciones*') ? 'active' : 'text-dark' }}">
                    <i class="bi bi-signpost-split"></i><span>Redirecciones</span>
                </a>
            </div>

            {{-- Empresa salió del menú principal: es solo el encabezado de la cotización.
                 Se accede desde Inicio, con perfil administrador. --}}
        @endif

        {{-- Cotizador (para vendedor y admin) --}}
        @if(auth()->user()->hasRole(['vendedor', 'admin']))
            <a href="{{ route('catalogo') }}"
               class="nav-link mb-2 d-flex align-items-center gap-2 {{ request()->routeIs('catalogo*') ? 'active' : 'text-dark' }}">
                <i class="bi bi-cart"></i>
                <span>Cotizador</span>
            </a>
            <a href="{{ route('solicitudes') }}"
               class="nav-link mb-2 d-flex align-items-center gap-2 {{ request()->routeIs('solicitudes*') ? 'active' : 'text-dark' }}">
                <i class="bi bi-clipboard-data"></i>
                <span>Solicitudes</span>
                @if($solicitudesPendientesCount > 0)
                    <span class="badge rounded-pill bg-danger ms-auto" title="Solicitudes pendientes" id="badgeSolicitudesPendientes">
                        {{ $solicitudesPendientesCount > 99 ? '99+' : $solicitudesPendientesCount }}
                    </span>
                @else
                    <span class="badge rounded-pill bg-danger ms-auto d-none" id="badgeSolicitudesPendientes">0</span>
                @endif
            </a>
            {{-- Links y Gestión de Stock se ocultaron a petición de Innpro:
                 el enlace al catálogo se maneja desde el Cotizador y el stock
                 desde Clientes/Productos. Las rutas siguen disponibles. --}}
        @endif

        {{-- Servicio Técnico (admin, técnico y vendedor) --}}
        @if(auth()->user()->hasAnyRole(['admin', 'tecnico', 'vendedor']))
            <a href="{{ route('servicio.index') }}"
               class="nav-link mb-2 d-flex align-items-center gap-2 {{ request()->routeIs('servicio.*') ? 'active' : 'text-dark' }}">
                <i class="bi bi-tools"></i>
                <span>Servicio Técnico</span>
            </a>
        @endif
    </nav>

    {{-- Botón Salir --}}
    <div class="mt-auto p-3 border-top">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-start gap-2">
                <i class="fas fa-sign-out-alt"></i>
                <span class="logout-label">Salir</span>
            </button>
        </form>
    </div>
</div>
