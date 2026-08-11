{{--
    Las cuatro pantallas del sitio web, siempre a la vista.

    Antes solo se llegaba a ellas desde el desplegable de la barra lateral, y
    desde dentro de una no había forma de saber que existían las otras tres ni
    qué se hacía en cada una. El subtítulo de cada pestaña es justo eso: para
    qué sirve, en una línea.
--}}
@php
    $pestanas = [
        ['ruta' => 'sitio.admin.secciones',     'patron' => 'sitio/secciones*',     'icono' => 'bi-layout-text-window-reverse', 'nombre' => 'Portada',        'para' => 'Los textos y las tarjetas de la página de inicio'],
        ['ruta' => 'sitio.admin.paginas',       'patron' => 'sitio/paginas*',       'icono' => 'bi-file-earmark-text',          'nombre' => 'Páginas',        'para' => 'Una página por servicio, y las noticias'],
        ['ruta' => 'sitio.admin.ajustes',       'patron' => 'sitio/ajustes*',       'icono' => 'bi-sliders',                    'nombre' => 'Ajustes y SEO',  'para' => 'Teléfono, dirección, redes y medición'],
        ['ruta' => 'sitio.admin.redirecciones', 'patron' => 'sitio/redirecciones*', 'icono' => 'bi-signpost-split',             'nombre' => 'Redirecciones',  'para' => 'Mandar una dirección vieja a la nueva'],
    ];
@endphp

<div class="row g-2 mb-4">
  @foreach ($pestanas as $p)
    @php $aqui = request()->is($p['patron']); @endphp
    <div class="col-6 col-lg-3">
      <a href="{{ route($p['ruta']) }}"
         class="d-block h-100 text-decoration-none border rounded-3 p-3 {{ $aqui ? 'border-primary bg-primary-subtle' : 'bg-white text-dark' }}"
         @if($aqui) aria-current="page" @endif>
        <div class="d-flex align-items-center gap-2 mb-1">
          <i class="bi {{ $p['icono'] }} {{ $aqui ? 'text-primary' : 'text-secondary' }}"></i>
          <span class="fw-semibold {{ $aqui ? 'text-primary' : '' }}">{{ $p['nombre'] }}</span>
        </div>
        <div class="small text-muted lh-sm">{{ $p['para'] }}</div>
      </a>
    </div>
  @endforeach
</div>
