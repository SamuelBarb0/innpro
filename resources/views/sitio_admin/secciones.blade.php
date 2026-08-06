<x-app-layout>
  <x-slot name="header">Sitio web · Secciones de la portada</x-slot>

  <div class="container py-4">
    @include('sitio_admin.partials.avisos')

    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h5 class="mb-1">Portada</h5>
        <p class="text-muted small mb-0">Los textos de cada bloque de la página de inicio.</p>
      </div>
      <a class="btn btn-outline-secondary" href="{{ route('landing') }}" target="_blank">Ver la portada</a>
    </div>

    @forelse($portada->bloques as $bloque)
      <div class="card shadow mb-3">
        <div class="card-body">
          <form method="POST" action="{{ route('sitio.admin.secciones.guardar', $bloque) }}">
            @csrf

            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="mb-0 text-uppercase text-muted">{{ $bloque->clave }}</h6>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="activo-{{ $bloque->id }}" name="activo" value="1"
                       @checked($bloque->activo)>
                <label class="form-check-label small" for="activo-{{ $bloque->id }}">Visible</label>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Línea pequeña</label>
                <input type="text" class="form-control" name="antetitulo" value="{{ $bloque->antetitulo }}">
              </div>
              <div class="col-md-8">
                <label class="form-label">Título</label>
                <input type="text" class="form-control" name="titulo" value="{{ $bloque->titulo }}">
              </div>
              <div class="col-12">
                <label class="form-label">Texto</label>
                <textarea class="form-control" name="texto" rows="3">{{ $bloque->texto }}</textarea>
                @if($bloque->clave === 'experiencia')
                  <div class="form-text">Deja una línea en blanco entre párrafos para separarlos.</div>
                @endif
              </div>
            </div>

            {{-- Las listas (tarjetas, viñetas, contadores) se muestran pero no se
                 editan todavía: cada una tiene su propia forma y meterlas en este
                 formulario genérico haría más fácil romperlas que arreglarlas. --}}
            @php
              $listas = collect($bloque->datos ?? [])->filter(fn ($v) => is_array($v) && array_is_list($v));
            @endphp
            @if($listas->isNotEmpty())
              <div class="mt-3 p-3 bg-light rounded">
                @foreach($listas as $nombre => $items)
                  <div class="small text-muted mb-1">
                    <strong>{{ ucfirst($nombre) }}</strong> ({{ count($items) }})
                  </div>
                  <ul class="small mb-2">
                    @foreach($items as $item)
                      <li>{{ $item['titulo'] ?? $item['texto'] ?? $item['etiqueta'] ?? json_encode($item, JSON_UNESCAPED_UNICODE) }}</li>
                    @endforeach
                  </ul>
                @endforeach
                <div class="small text-muted mb-0">
                  Estas listas todavía se editan desde el código. Pídelas si necesitas cambiarlas.
                </div>
              </div>
            @endif

            <button class="btn btn-sm btn-primary mt-3">Guardar sección</button>
          </form>
        </div>
      </div>
    @empty
      <div class="alert alert-warning">La portada no tiene secciones cargadas.</div>
    @endforelse
  </div>
</x-app-layout>
